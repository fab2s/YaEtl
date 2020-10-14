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
 * Class PdoExtractor
 */
class PdoExtractor extends DbExtractorAbstract
{
    use PdoExtractorTrait;

    /**
     * Instantiate PdoExtractor
     *
     * @param \PDO        $pdo
     * @param string|null $extractQuery
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function __construct(\PDO $pdo, ?string $extractQuery = null)
    {
        $this->configurePdo($pdo);

        parent::__construct($extractQuery);
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
     * This method sets offset and limit in the query
     * WARNING : if you set an offset without limit,
     * the limit will be set to  $this->maxdefaultLimit
     *
     * @return string the paginated query with current offset and limit
     */
    protected function getPaginatedQuery(): string
    {
        return $this->extractQuery . $this->getLimitOffsetBit();
    }
}
