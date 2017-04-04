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
     * This is hte default limit applied when no limit
     * is set and an offset is set.
     * It's 2^31 – 1 = 2147483647 = max 32bit
     * Use 2^63 − 1 = 9223372036854775807 = max 64bit
     * if your os and dbms are 64bit
     *
     * @var int
     */
    protected $maxdefaultLimit = 2147483647;

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

    /**
     * @param string $extractQuery which MUST NOT contain the LIMIT/OFFSET bit
     *
     * @return $this
     */
    public function setExtractQuery($extractQuery)
    {
        if (empty($this->pdo)) {
            throw new \Exception('[YaEtl] Pdo must be set');
        }

        parent::setExtractQuery($extractQuery);

        return $this;
    }

    /**
     * @return string
     */
    protected function getLimitOffsetBit()
    {
        return ' ' . \implode('', [
            $this->limit ? ' LIMIT ' . (int) $this->limit : ($this->offset ? ' LIMIT ' . $this->maxdefaultLimit : ''),
            $this->offset ? ' OFFSET ' . (int) $this->offset : '',
            ';',
        ]);
    }
}
