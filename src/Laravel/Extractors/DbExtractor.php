<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Laravel\Extractors;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\YaEtlException;
use fab2s\YaEtl\Extractors\PdoExtractor;
use Illuminate\Database\Query\Builder;

/**
 * Class DbExtractor
 */
class DbExtractor extends PdoExtractor
{
    /**
     * The record collection structure
     *
     * @var \SplDoublyLinkedList
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
     * @param Builder $extractQuery
     *
     * @throws YaEtlException
     * @throws NodalFlowException
     */
    public function __construct(Builder $extractQuery)
    {
        $this->setExtractQuery($extractQuery);

        parent::__construct($extractQuery->getConnection()->getPdo());
    }

    /**
     * Set the extract query
     *
     * @param $extractQuery
     *
     * @throws YaEtlException
     *
     * @return $this
     */
    public function setExtractQuery($extractQuery)
    {
        if (!($extractQuery instanceof Builder)) {
            throw new YaEtlException('Argument 1 passed to ' . __METHOD__ . ' must be an instance of ' . Builder::class . ', ' . \gettype($extractQuery) . ' given');
        }

        parent::setExtractQuery($extractQuery);

        return $this;
    }

    /**
     * This method sets offset and limit in the query
     *
     * @return string the paginated query with current offset and limit
     */
    protected function getPaginatedQuery()
    {
        $extractQuery = $this->extractQuery
            ->skip($this->offset)
            ->take($this->batchSize);
        $this->queryBindings = $extractQuery->getRawBindings();
        $this->queryBindings = $this->queryBindings['where'];

        return $extractQuery->toSql();
    }
}
