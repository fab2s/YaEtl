<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\YaEtlException;

/**
 * Abstract Class UniqueKeyExtractorAbstract
 */
abstract class UniqueKeyExtractorAbstract extends DbExtractorAbstract implements JoinableInterface
{
    /**
     * The joined record collection
     *
     * @var array
     */
    protected $joinedRecords;

    /**
     * The composite key representation
     *
     * @var array|string
     */
    protected $compositeKey;

    /**
     * The unique key name
     *
     * @var string|null
     */
    protected $uniqueKeyName;

    /**
     * The unique key alias
     *
     * @var string|null
     */
    protected $uniqueKeyAlias;

    /**
     * List of unique key values, used to be joinable
     *
     * @var array
     */
    protected $uniqueKeyValues = [];

    /**
     * unique key value buffer, used to align batch sizes
     *
     * @var array
     */
    protected $uniqueKeyValueBuffer = [];

    /**
     * This Node's OnClose object if any
     *
     * @var OnClauseInterface|null
     */
    protected $onClose;

    /**
     * List of all joiners by their OnClose constraints
     *
     * @var array of OnClauseInterface
     */
    protected $joinerOnCloses = [];

    /**
     * The record map
     *
     * @var array
     */
    protected $recordMap;

    /**
     * The Joinable we may be joining against
     *
     * @var JoinableInterface
     */
    protected $joinFrom;

    /**
     * Generic extraction from tables with unique (composite) key
     *
     * @param string|null  $extractQuery
     * @param array|string $uniqueKeySetup can be either a unique key name as
     *                                     string
     *                                     `'(table.)compositeKeyName' // ('id' by default)`
     *
     *                      or an array :
     *                      `['(table.)compositeKey1'] // single unique key`
     *                      `['(table.)compositeKey1', '(table.)compositeKey2', ] // composite unique key`
     *
     *                      or an associative array in case you are using aliases :
     *                      `[
     *                          '(table.)compositeKey1' => 'aliasNameAsInRecord',
     *                      ]`
     *
     *                      and :
     *                      `[
     *                          '(table.)compositeKey1' => 'aliasNameAsInRecord1',
     *                          '(table.)compositeKey2' => 'aliasNameAsInRecord2',
     *                          // ...
     *                      ]`
     *
     * @throws NodalFlowException
     */
    public function __construct(?string $extractQuery = null, $uniqueKeySetup = 'id')
    {
        $this->configureUniqueKey($uniqueKeySetup);

        $this->nodeIncrements = array_replace($this->nodeIncrements, [
            'num_join' => 0,
        ]);

        parent::__construct($extractQuery);
    }

    /**
     * Get this Joiner's ON clause. Only used in Join mode
     *
     * @return OnClauseInterface|null
     */
    public function getOnClause(): ?OnClauseInterface
    {
        return $this->onClose;
    }

    /**
     * Set Joiner's ON clause. Only used in Join mode
     *
     * @param OnClauseInterface $onClause
     *
     * @return $this
     */
    public function setOnClause(OnClauseInterface $onClause): JoinableInterface
    {
        $this->onClose = $onClause;

        return $this;
    }

    /**
     * Register ON clause field mapping. Used by an eventual joiner to this
     *
     * @param OnClauseInterface $onClause
     *
     * @return $this
     */
    public function registerJoinerOnClause(OnClauseInterface $onClause): JoinableInterface
    {
        $this->joinerOnCloses[] = $onClause;

        return $this;
    }

    /**
     * Register the extractor we would be joining against
     *
     * @param JoinableInterface $joinFrom
     *
     * @throws YaEtlException
     *
     * @return $this
     */
    public function setJoinFrom(JoinableInterface $joinFrom): JoinableInterface
    {
        // at least make sure this joinable extends this very class
        // to enforce getRecordMap() type
        if (!is_a($joinFrom, self::class)) {
            throw new YaEtlException('The extractor joined against is not compatible, expected implementation of: ' . self::class . "\ngot: " . \get_class($joinFrom));
        }

        if (preg_match('`^.+?order\s+by.*?$`is', $this->extractQuery)) {
            throw new YaEtlException("A Joiner must not order its query got: $this->extractQuery");
        }

        // since we are joining, we are not a traversable anymore
        $this->isATraversable = false;
        // and we return a value
        $this->isAReturningVal = true;
        $this->joinFrom        = $joinFrom;

        return $this;
    }

    /**
     * Get record map, used to allow joiners to join
     *
     * @param string|null $fromKeyAlias The from unique key to get the map against
     *                                  as exposed in the record
     *
     * @return array [keyValue1, keyValue2, ...]
     */
    public function getRecordMap(?string $fromKeyAlias = null)
    {
        return $fromKeyAlias === null ? $this->recordMap : $this->recordMap[$fromKeyAlias];
    }

    /**
     * Trigger extract
     *
     * @param mixed $param
     *
     * @throws YaEtlException
     *
     * @return bool
     */
    public function extract($param = null): bool
    {
        if (isset($this->joinFrom)) {
            return $this->joinExtract();
        }

        // enforce limit if any is set
        if ($this->isLimitReached()) {
            return false;
        }

        $this->enforceBatchSize();
        if ($this->fetchRecords()) {
            $this->incrementOffset()
                ->genRecordMap();

            return true;
        }

        return false;
    }

    /**
     * Enforce batch size consistency
     *
     * @return static
     */
    public function enforceBatchSize(): ExtractorBatchLimitInterface
    {
        if (isset($this->joinFrom)) {
            // obey batch size to allow fromer to fetch a huge amount of records
            // while keeping the "where in" query size under control by splitting
            // it into several chunks.
            // append uniqueKeyValues to uniqueKeyValueBuffer
            $this->uniqueKeyValueBuffer = \array_replace($this->uniqueKeyValueBuffer, $this->uniqueKeyValues);
            // only keep batchSize
            $this->uniqueKeyValues      = \array_slice($this->uniqueKeyValueBuffer, 0, $this->batchSize, true);
            // drop consumed keys
            $this->uniqueKeyValueBuffer = \array_slice($this->uniqueKeyValueBuffer, $this->batchSize, null, true);

            return $this;
        }

        parent::enforceBatchSize();

        return $this;
    }

    /**
     * Execute the Join
     *
     * @param mixed $record
     *
     * @throws YaEtlException
     *
     * @return mixed The result of the join
     */
    public function exec($record = null)
    {
        $uniqueKeyValue = $record[$this->uniqueKeyAlias];

        if (isset($this->joinedRecords[$uniqueKeyValue])) {
            $joinRecord = $this->joinedRecords[$uniqueKeyValue];
            unset($this->joinedRecords[$uniqueKeyValue]);
            if ($joinRecord === false) {
                // skip record
                $this->getCarrier()->continueFlow();

                return $record;
            }

            return $this->onClose-> /* @scrutinizer ignore-call */ merge($record, $joinRecord);
        }

        if ($this->joinExtract()) {
            return $this->exec($record);
        }

        // something is wrong as uniqueKeyValueBuffer should
        // never run out until the fromer stop providing records
        // which means we do not want to reach here
        throw new YaEtlException('Record map mismatch between Joiner ' . \get_class($this) . ' and Fromer ' . \get_class($this->joinFrom));
    }

    /**
     * Trigger an extract in join mode
     *
     * @throws YaEtlException
     *
     * @return bool
     */
    protected function joinExtract(): bool
    {
        // join mode, get record map
        $this->uniqueKeyValues = $this->joinFrom->getRecordMap($this->onClose-> /* @scrutinizer ignore-call */ getFromKeyAlias());
        // limit does not apply in join mode
        $this->enforceBatchSize();
        if (empty($this->uniqueKeyValues)) {
            return false;
        }

        if ($this->fetchJoinedRecords()) {
            $this->getCarrier()->getFlowMap()->incrementNode($this->getId(), 'num_join');
            // gen record map before we set defaults
            $this->genRecordMap()
                ->setDefaultExtracted();

            return true;
        }

        return false;
    }

    /**
     * Configure the unique key
     *
     * @param array|string $uniqueKeySetup can be either a unique key name as
     *                                     string
     *                                     `'(table.)compositeKeyName' // ('id' by default)`
     *
     *                      or an array :
     *                      `['(table.)compositeKey1'] // single unique key`
     *                      `['(table.)compositeKey1', '(table.)compositeKey2', ] // composite unique key`
     *
     *                      or an associative array in case you are using aliases :
     *                      `[
     *                          '(table.)compositeKey1' => 'aliasNameAsInRecord',
     *                      ]`
     *
     *                      and :
     *                      `[
     *                          '(table.)compositeKey1' => 'aliasNameAsInRecord1',
     *                          '(table.)compositeKey2' => 'aliasNameAsInRecord2',
     *                          // ...
     *                      ]`
     *
     * @return static
     */
    protected function configureUniqueKey($uniqueKeySetup): self
    {
        $uniqueKeySetup            = \is_array($uniqueKeySetup) ? $uniqueKeySetup : [$uniqueKeySetup];
        $this->compositeKey        = [];
        $this->uniqueKeyName       = null;
        $this->uniqueKeyAlias      = null;
        foreach ($uniqueKeySetup as $key => $value) {
            if (\is_numeric($key)) {
                $compositeKeyName  = $this->cleanUpKeyName($value);
                $compositeKeyParts = \explode('.', $compositeKeyName);
                $compositeKeyAlias = \end($compositeKeyParts);
            } else {
                $compositeKeyName  = $this->cleanUpKeyName($key);
                $compositeKeyAlias = $this->cleanUpKeyName($value);
            }

            $this->compositeKey[$compositeKeyName] = $compositeKeyAlias;
        }

        if (\count($this->compositeKey) === 1) {
            $this->uniqueKeyName  = \key($this->compositeKey);
            $this->uniqueKeyAlias = \current($this->compositeKey);
        }

        return $this;
    }

    /**
     * Clean up key names
     *
     * @param string $keyName
     *
     * @return string
     */
    protected function cleanUpKeyName($keyName): string
    {
        return \trim($keyName, '` ');
    }

    /**
     * Prepare record set to obey join mode eg return record = true
     * to break branch execution when no match are found in join more
     * or default to be later merged in left join mode
     *
     * @return static
     */
    protected function setDefaultExtracted(): self
    {
        $defaultRecord    = $this->onClose-> /* @scrutinizer ignore-call */ isLeftJoin() ? $this->onClose-> /* @scrutinizer ignore-call */ getDefaultRecord() : false;
        $defaultExtracted = \array_fill_keys($this->uniqueKeyValues, $defaultRecord);

        $this->joinedRecords = \array_replace($defaultExtracted, /* @scrutinizer ignore-type */ $this->joinedRecords);

        return $this;
    }

    /**
     * Generate record map
     *
     * @throws YaEtlException
     *
     * @return static
     */
    protected function genRecordMap(): self
    {
        // here we need to build record map ready for all joiners
        $this->recordMap = [];
        foreach ($this->joinerOnCloses as $onClose) {
            $fromKeyAlias = $onClose->getFromKeyAlias();
            if (isset($this->recordMap[$fromKeyAlias])) {
                // looks like there is more than
                // one joiner on this key
                continue;
            }

            // generate record map
            $this->recordMap[$fromKeyAlias] = [];
            $map                            = &$this->recordMap[$fromKeyAlias];
            // we do not want to map defaults here as we do not want joiners
            // to this to join on null
            // we could optimize a little bit for cases where
            // $this->joinedRecords is an indexed array on the proper key but ...
            foreach ($this->getExtracted() as $record) {
                if (!isset($record[$fromKeyAlias])) {
                    // Since we do not enforce key alias existence during init
                    // we have to do it here
                    throw new YaEtlException("From Key Alias not found in record: $fromKeyAlias");
                }

                $fromKeyValue       = $record[$fromKeyAlias];
                $map[$fromKeyValue] = $fromKeyValue;
            }
        }

        return $this;
    }

    /**
     * fetch records when joining against another extractor
     * They should still be send to setExtracted to be made
     * available in map generation for eventual joiners to
     * this joiner and also fill up joinedRecords as an
     * associative array indexed by the proper join key
     *
     * @return bool
     */
    abstract protected function fetchJoinedRecords(): bool;
}
