<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

/**
 * trait PdoExtractorTrait
 */
trait PdoExtractorTrait
{
    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var bool
     */
    protected $driverBufferedQuery;

    /**
     * @var array
     */
    protected $supportedDrivers = [
        'mysql'  => 'mysql',
        'sqlite' => 'sqlite',
        'pgsql'  => 'pgsql',
    ];

    /**
     * @var string
     */
    protected $dbDriverName;

    /**
     * @var array
     */
    protected $queryBindings;

    /**
     * leave no trace
     */
    public function __destruct()
    {
        if ($this->dbDriverName === 'mysql' && $this->driverBufferedQuery) {
            // set driver state back to where we met
            $this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

    /**
     * @param \PDO $pdo
     */
    public function configurePdo(\PDO $pdo)
    {
        $this->pdo          = $pdo;
        $this->dbDriverName = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if (!isset($this->supportedDrivers[$this->dbDriverName])) {
            throw new \Exception('[YaEtl] Pdo driver not supported, must be one of: ' . \implode(', ', \array_keys($this->supportedDrivers)));
        }

        if ($this->dbDriverName === 'mysql') {
            // buffered wueries can have great performance impact
            // with large data sets
            $this->driverBufferedQuery = $this->pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);

            if ($this->driverBufferedQuery) {
                // disable buffered queries as we should be querying by a lot
                $this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            }
        }
    }
}
