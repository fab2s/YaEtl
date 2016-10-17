<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Yaetl\Extractors;

use fab2s\YaEtl\Extractors\PdoExtractorTrait;

/**
 * class PdoUniqueKeyExtractor
 */
class PdoUniqueKeyExtractor extends UniqueKeyExtractorAbstract
{
    use PdoExtractorTrait;

    /**
     * generic extraction from tables with unique (composite) key
     *
     * @param \PDO         $pdo
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
    public function __construct(\PDO $pdo, $extractQuery = null, $uniqueKey = 'id')
    {
        $this->configurePdo($pdo);
        parent::__construct($extractQuery, $uniqueKey);
    }

    /**
     * leave no trace
     * implement here to allow easier overidding
     */
    public function __destruct()
    {
        if ($this->driverBufferedQuery) {
            // set driver state back to where we met
            $this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

    /**
     * @return bool
     */
    public function fetchRecords()
    {
        $extractQuery = $this->getPaginatedQuery();

        $query = $this->pdo->prepare($extractQuery);
        if (!$query->execute(!empty($this->queryBindings) ? $this->queryBindings : null)) {
            return false;
        }

        if (isset($this->joinFrom)) {
            $this->extracted = [];
        } else {
            $this->extracted = new \SplDoublyLinkedList;
        }

        $hasRecord = false;
        while ($record = $query->fetch(\PDO::FETCH_ASSOC)) {
            if (isset($this->joinFrom)) {
                $this->extracted[$record[$this->uniqueKeyName]] = $record;
            } else {
                $this->extracted->push($record);
            }

            $hasRecord = true;
        }

        $query->closeCursor();
        unset($query);

        return $hasRecord;
    }
}
