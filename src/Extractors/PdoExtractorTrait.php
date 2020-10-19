<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

use fab2s\NodalFlow\YaEtlException;

/**
 * trait PdoExtractorTrait
 */
trait PdoExtractorTrait
{
    /**
     * The PDO connection
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Keep track of Buffered queries status in case we need / can to tweak it
     *
     * @var bool
     */
    protected $driverBufferedQuery;

    /**
     * List of supported drivers
     *
     * @var array
     */
    protected $supportedDrivers = [
        'mysql'  => 'mysql',
        'sqlite' => 'sqlite',
        'pgsql'  => 'pgsql',
    ];

    /**
     * Current driver name
     *
     * @var string
     */
    protected $dbDriverName;

    /**
     * Query bindings
     *
     * @var array
     */
    protected $queryBindings;

    /**
     * Leave no trace
     */
    public function __destruct()
    {
        if ($this->dbDriverName === 'mysql' && $this->driverBufferedQuery) {
            // set driver state back to where we met
            $this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

    /**
     * Properly set up PDO connection
     *
     * @param \PDO $pdo
     *
     * @throws YaEtlException
     */
    public function configurePdo(\PDO $pdo)
    {
        $this->pdo          = $pdo;
        $this->dbDriverName = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if (!isset($this->supportedDrivers[$this->dbDriverName])) {
            throw new YaEtlException('Pdo driver not supported, must be one of: ' . \implode(', ', \array_keys($this->supportedDrivers)));
        }

        if ($this->dbDriverName === 'mysql') {
            // buffered queries can have great performance impact
            // with large data sets
            $this->driverBufferedQuery = $this->pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);

            if ($this->driverBufferedQuery) {
                // disable buffered queries as we should be querying by a lot
                $this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            }
        }
    }

    /**
     * Fetch records
     *
     * @return bool
     */
    public function fetchRecords(): bool
    {
        $extractQuery = $this->getPaginatedQuery();
        $statement    = $this->pdo->prepare($extractQuery);
        if (!$statement->execute(!empty($this->queryBindings) ? $this->queryBindings : null)) {
            return false;
        }

        // It is most likely better to proxy all records
        // as it also makes sure that we read from db as
        // fast as possible and release pressure asap
        $collection = new \SplDoublyLinkedList;
        while ($record = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $collection->push($record);
        }

        $statement->closeCursor();
        unset($statement);

        /* @var $this DbExtractorAbstract */
        $this->setExtractedCollection($collection);

        return !$collection->isEmpty();
    }

    /**
     * @return string the paginated query with current offset and limit
     */
    abstract protected function getPaginatedQuery(): string;
}
