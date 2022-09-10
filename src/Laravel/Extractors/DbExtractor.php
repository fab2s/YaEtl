<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Laravel\Extractors;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Extractors\DbExtractorAbstract;
use fab2s\YaEtl\Extractors\PaginatedQueryInterface;
use fab2s\YaEtl\Extractors\PdoExtractor;
use fab2s\YaEtl\YaEtlException;
use Illuminate\Database\Query\Builder;

/**
 * Class DbExtractor
 */
class DbExtractor extends PdoExtractor implements PaginatedQueryInterface
{
    use DelayedExtractQueryTrait;

    /**
     * The record collection structure
     *
     * @var iterable
     */
    protected $extracted;

    /**
     * The query object
     *
     * @var Builder
     */
    protected $extractQuery;

    /**
     * Instantiate the DbExtractor
     *
     * @param Builder|null $extractQuery
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function __construct(?Builder $extractQuery = null)
    {
        if ($extractQuery !== null) {
            $this->setExtractQuery($extractQuery);
        }

        // delay configuring pdo to flow start
        DbExtractorAbstract::__construct();
    }

    /**
     * This method sets offset and limit in the query
     *
     * @return string the paginated query with current offset and limit
     */
    public function getPaginatedQuery(): string
    {
        $extractQuery = $this->extractQuery
            ->offset($this->offset)
            ->limit($this->batchSize);
        $this->queryBindings = $extractQuery->getRawBindings();
        $this->queryBindings = $this->queryBindings['where'];

        return $extractQuery->toSql();
    }
}
