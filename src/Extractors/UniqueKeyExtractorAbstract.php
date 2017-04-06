<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

/**
 * Abstract Class UniqueKeyExtractorAbstract
 */
abstract class UniqueKeyExtractorAbstract extends DbExtractorAbstract implements JoinableInterface
{
    /**
     * the unique key name
     *
     * @var array|string
     */
    protected $compositeKey;

    /**
     * @var string
     */
    protected $uniqueKeyName;

    /**
     * @var string
     */
    protected $uniqueKeyAlias;

    /**
     * @var array
     */
    protected $uniqueKeyValues = [];

    /**
     * @var array
     */
    protected $uniqueKeyValueBuffer = [];

    /**
     * @var callable
     */
    protected $merger;

    /**
     * @var OnClauseInterface
     */
    protected $onClose;

    /**
     * @var array of OnClauseInterface
     */
    protected $joinerOnCloses = [];

    /**
     * the record map
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
     * generic extraction from tables with unique (composite) key
     *
     * @param string       $extractQuery
     * @param array|string $uniqueKey    can be either a unique key name as
     *                                   string
     *                                   '(table.)compositeKeyName' // ('id' by default)
     *
     *                      or an array :
     *                      ['(table.)compositeKey1'] // single unique key
     *                      ['(table.)compositeKey1', '(table.)compositeKey2', ] // composite unique key
     *
     *                      or an associative array in case you are using aliases :
     *                      [
     *                          '(table.)compositeKey1' => 'aliasNameAsInRecord',
     *                      ]
     *
     *                      and :
     *                      [
     *                          '(table.)compositeKey1' => 'aliasNameAsInRecord1',
     *                          '(table.)compositeKey2' => 'aliasNameAsInRecord2',
     *                          // ...
     *                      ]
     */
    public function __construct($extractQuery = null, $uniqueKey = 'id')
    {
        $this->configureUniqueKey($uniqueKey);

        parent::__construct($extractQuery);
    }

    /**
     * get Joiner's ON clause. Only used in Join mode
     *
     * @return OnClauseInterface
     */
    public function getOnClause()
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
    public function setOnClause(OnClauseInterface $onClause)
    {
        $this->onClose = $onClause;

        return $this;
    }

    /**
     * register ON clause field mapping. Used by an eventual joiner to this
     *
     * @param OnClauseInterface $onClause
     *
     * @return $this
     */
    public function registerJoinerOnClause(OnClauseInterface $onClause)
    {
        $this->joinerOnCloses[] = $onClause;

        return $this;
    }

    /**
     * @param JoinableInterface $joinFrom
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setJoinFrom(JoinableInterface $joinFrom)
    {
        // at least make sure this joinable extends this very class
        // to enforce getRecordMap() type
        if (!is_a($joinFrom, self::class)) {
            throw new \Exception('[YaEtl] From extractor is not compatible, expected implementation of: ' . self::class . "\ngot: " . \get_class($joinFrom));
        }

        if (preg_match('`^(.+)(order\s+by.*)$`is', $this->extractQuery)) {
            throw new \Exception("[YaEtl] A Joiner must not order its query got: $this->extractQuery");
        }

        // since we are joining, we are not a traversable anymore
        $this->isATraversable = false;
        // and we return a value
        $this->isAReturningVal = true;
        $this->joinFrom        = $joinFrom;

        return $this;
    }

    /**
     * @param string $fromKeyAlias The from unique key to get the map against
     *                             as exposed in the record
     *
     * @return array [keyValue1, keyValue2, ...]
     */
    public function getRecordMap($fromKeyAlias = null)
    {
        return $fromKeyAlias === null ? $this->recordMap : $this->recordMap[$fromKeyAlias];
    }

    /**
     * @param mixed $param
     *
     * @return bool
     */
    public function extract($param = null)
    {
        if (isset($this->joinFrom)) {
            // join mode, get record map
            $this->uniqueKeyValues = $this->joinFrom->getRecordMap($this->onClose->getFromKeyAlias());
            // limit does not apply in join mode
            $this->enforceBatchSize();
            if (empty($this->uniqueKeyValues)) {
                return false;
            }

            if ($this->fetchRecords()) {
                // gen record map before we set defaults
                $this->genRecordMap()
                    ->setDefaultExtracted();

                return true;
            }

            return false;
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
     * @return $this
     */
    public function enforceBatchSize()
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
     * @param mixed $record
     *
     * @return mixed The result of the join
     */
    public function exec($record)
    {
        $uniqueKeyValue = $record[$this->uniqueKeyAlias];

        if (isset($this->extracted[$uniqueKeyValue])) {
            $joinRecord = $this->extracted[$uniqueKeyValue];
            unset($this->extracted[$uniqueKeyValue]);
            if ($joinRecord === false) {
                // skip record
                $this->carrier->continueFlow();

                return $record;
            }

            ++$this->numRecords;

            return $this->onClose->merge($record, $joinRecord);
        } elseif (isset($this->uniqueKeyValueBuffer[$uniqueKeyValue])) {
            // uniqueKeyValueBuffer should never run out until
            // the fromer stop providing records which mean
            // we do not reach here. Case left is when this
            // batchSize < fromer batchSize.
            // trigger extract
            if ($this->extract()) {
                return $this->exec($record);
            }
        } elseif ($this->extract()) {
            return $this->exec($record);
        }

        // something is wrong
        throw new \Exception('[YaEtl] Record map missmatch betwen Joiner ' . \get_class($this) . ' and Fromer ' . \get_class($this->joinFrom));
    }

    /**
     * @param array|string $uniqueKey can be either a unique key name as
     *                                string
     *                                '(table.)compositeKeyName' // ('id' by default)
     *
     *                      or an array :
     *                      ['(table.)compositeKey1'] // single unique key
     *                      ['(table.)compositeKey1', '(table.)compositeKey2', ] // composite unique key
     *
     *                      or an associative array in case you are using aliases :
     *                      [
     *                          '(table.)compositeKey1' => 'aliasNameAsInRecord',
     *                      ]
     *
     *                      and :
     *                      [
     *                          '(table.)compositeKey1' => 'aliasNameAsInRecord1',
     *                          '(table.)compositeKey2' => 'aliasNameAsInRecord2',
     *                          // ...
     *                      ]
     *
     * @return $this
     */
    protected function configureUniqueKey($uniqueKey)
    {
        $uniqueKey            = \is_array($uniqueKey) ? $uniqueKey : [$uniqueKey];
        $this->compositeKey   = [];
        $this->uniqueKeyName  = null;
        $this->uniqueKeyAlias = null;
        foreach ($uniqueKey as $key => $value) {
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
     * @param string $keyName
     *
     * @return string
     */
    protected function cleanUpKeyName($keyName)
    {
        return \trim($keyName, '` ');
    }

    /**
     * prepare record set to obey join mode eg return record = true
     * to break branch execution when no match are found in join more
     * or default to be later merged in left join mode
     *
     * @return $this
     */
    protected function setDefaultExtracted()
    {
        if ($this->joinFrom !== null) {
            $defaultExtracted = \array_fill_keys($this->uniqueKeyValues, $this->onClose->isLeftJoin() ? $this->onClose->getDefaultRecord() : false);

            foreach ($defaultExtracted as $keyValue => &$default) {
                // extracted is an array in join mode
                if (isset($this->extracted[$keyValue])) {
                    $default = $this->extracted[$keyValue];
                }
            }

            $this->extracted = $defaultExtracted;
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function genRecordMap()
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

            // generate rercord map
            $this->recordMap[$fromKeyAlias] = [];

            $map = &$this->recordMap[$fromKeyAlias];
            // we do not want to map defaults here as we do not want joiners
            // to this to join on null
            // we could optimize a little bit for cases where
            // $this->extracted is an indexed array on the proper key but ...
            foreach ($this->extracted as $record) {
                if (!isset($record[$fromKeyAlias])) {
                    // Since we do not enforce key alias existance during init
                    // we have to do it here
                    throw new \Exception("[YaEtl] From Key Alias not found in record: $fromKeyAlias");
                }

                $fromKeyValue       = $record[$fromKeyAlias];
                $map[$fromKeyValue] = $fromKeyValue;
            }
        }

        return $this;
    }
}
