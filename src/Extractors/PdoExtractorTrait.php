<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

use fab2s\YaEtl\YaEtlException;
use PDO;
use Pdo\Mysql;
use SplDoublyLinkedList;

/**
 * trait PdoExtractorTrait
 */
trait PdoExtractorTrait
{
    /**
     * The PDO connection
     *
     * @var PDO
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
            $this->pdo->setAttribute(static::bufferedQueryAttribute(), true);
        }
    }

    /**
     * PDO::MYSQL_ATTR_USE_BUFFERED_QUERY is deprecated since php 8.5 in favor of
     * Pdo\Mysql::ATTR_USE_BUFFERED_QUERY, which only exists since php 8.4
     * Both constants only exist when the pdo_mysql extension is loaded,
     * which is granted in mysql driver context
     */
    public static function bufferedQueryAttribute(): int
    {
        return PHP_VERSION_ID >= 80400 ? Mysql::ATTR_USE_BUFFERED_QUERY : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
    }

    /**
     * Properly set up PDO connection
     *
     *
     *
     * @return static
     *
     * @throws YaEtlException
     */
    public function configurePdo(PDO $pdo): self
    {
        $this->pdo          = $pdo;
        $this->dbDriverName = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if (! ($this instanceof PaginatedQueryInterface) && ! isset($this->supportedDrivers[$this->dbDriverName])) {
            throw new YaEtlException(\get_class($this) . ' does not implement PaginatedQueryInterface and does not uses a supported Pdo driver, supported drivers are: ' . \implode(', ', \array_keys($this->supportedDrivers)));
        }

        if ($this->dbDriverName === 'mysql') {
            // buffered queries can have great performance impact
            // with large data sets
            $bufferedQueryAttribute    = static::bufferedQueryAttribute();
            $this->driverBufferedQuery = $this->pdo->getAttribute($bufferedQueryAttribute);

            if ($this->driverBufferedQuery) {
                // disable buffered queries as we should be querying by a lot
                $this->pdo->setAttribute($bufferedQueryAttribute, false);
            }
        }

        return $this;
    }

    /**
     * Fetch records
     */
    public function fetchRecords(): bool
    {
        $extractQuery = $this->getPaginatedQuery();
        $statement    = $this->pdo->prepare($extractQuery);
        if (! $statement->execute(! empty($this->queryBindings) ? $this->queryBindings : null)) {
            return false;
        }

        // It is most likely better to proxy all records
        // as it also makes sure that we read from db as
        // fast as possible and release pressure asap
        $collection = new SplDoublyLinkedList;
        while ($record = $statement->fetch(PDO::FETCH_ASSOC)) {
            $collection->push($record);
        }

        $statement->closeCursor();
        unset($statement);

        /* @var $this DbExtractorAbstract */
        $this->setExtractedCollection($collection);

        return ! $collection->isEmpty();
    }

    /**
     * @return string the paginated query with current offset and limit
     */
    abstract public function getPaginatedQuery(): string;
}
