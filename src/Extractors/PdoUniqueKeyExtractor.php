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
 * class PdoUniqueKeyExtractor
 */
class PdoUniqueKeyExtractor extends UniqueKeyExtractorAbstract
{
    use PdoExtractorTrait {
        fetchRecords as pdoFetchRecords;
    }

    /**
     * Generic extraction from tables with unique (composite) key
     *
     * @param \PDO         $pdo
     * @param string|null  $extractQuery
     * @param array|string $uniqueKey    can be either a unique key name as
     *                                   string
     *                                   `'(table.)compositeKeyName' // ('id' by default)`
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
     * @throws YaEtlException
     * @throws NodalFlowException
     */
    public function __construct(\PDO $pdo, ?string $extractQuery = null, $uniqueKey = 'id')
    {
        $this->configurePdo($pdo);

        parent::__construct($extractQuery, $uniqueKey);
    }

    /**
     * Leave no trace
     * implement here to allow easier overriding
     */
    public function __destruct()
    {
        if ($this->driverBufferedQuery) {
            // set driver state back to where we met
            $this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

    /**
     * Fetch records
     *
     * @return bool
     */
    public function fetchRecords(): bool
    {
        if (!$this->pdoFetchRecords()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function fetchJoinedRecords(): bool
    {
        $extractQuery = $this->getPaginatedQuery();
        $statement    = $this->pdo->prepare($extractQuery);
        if (!$statement->execute(!empty($this->queryBindings) ? $this->queryBindings : null)) {
            return false;
        }

        $this->joinedRecords = [];
        while ($record = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $this->joinedRecords[$record[$this->uniqueKeyName]] = $record;
        }

        $statement->closeCursor();
        unset($statement);
        // still set this as extracted as we build
        // record map in both from and join context
        $this->setExtractedCollection($this->joinedRecords);

        return !empty($this->joinedRecords);
    }

    /**
     * This method sets offset and limit in the query
     * WARNING : if you set an offset without limit,
     * the limit will be set to  $this->maxdefaultLimit
     *
     * @return string the paginated query with current offset and limit
     */
    protected function getPaginatedQuery(): string
    {
        if ($this->joinFrom) {
            $this->queryBindings = array_values($this->uniqueKeyValues);

            $whereOrAndStr = stripos($this->extractQuery, 'WHERE') !== false ? 'AND' : 'WHERE';

            return $this->extractQuery . " $whereOrAndStr $this->uniqueKeyName IN (" . implode(',', array_fill(0, count($this->uniqueKeyValues), '?')) . ')';
        }

        return $this->extractQuery . $this->getLimitOffsetBit();
    }
}
