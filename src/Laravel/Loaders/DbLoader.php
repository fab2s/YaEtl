<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Laravel\Loaders;

use DB;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Loaders\LoaderAbstract;
use Illuminate\Database\Query\Builder;

/**
 * Class DbLoader
 */
class DbLoader extends LoaderAbstract
{
    /**
     * Array of fields used in the where clause (select and update)
     *
     * @var array
     */
    protected $whereFields;

    /**
     * The query object
     *
     * @var Builder
     */
    protected $loadQuery;

    /**
     * Instantiate the DbLoader
     *
     * @param Builder|null $loadQuery
     *
     * @throws NodalFlowException
     */
    public function __construct(Builder $loadQuery = null)
    {
        if ($loadQuery !== null) {
            $this->setLoadQuery($loadQuery);
        }

        parent::__construct();
    }

    /**
     * Set the Load query
     *
     * @param Builder $loadQuery
     *
     * @return static
     */
    public function setLoadQuery(Builder $loadQuery): self
    {
        $this->loadQuery = $loadQuery;

        return $this;
    }

    /**
     * Set proper WHERE fields
     *
     * @param array $whereFields
     *
     * @return static
     */
    public function setWhereFields(array $whereFields): self
    {
        $this->whereFields = $whereFields;

        return $this;
    }

    /**
     * This method does not implement multi inserts and will
     * perform one query per record, which is also why flush
     * is left alone
     * We assume here that transformed data is a name/value pair
     * array of fields to update/insert
     *
     * @param array $param The record to load
     *
     * @return mixed|void
     */
    public function exec($param = null)
    {
        // clone query object in order to prevent where clause stacking
        $loadQuery   = clone $this->loadQuery;
        $whereClause = \array_intersect_key($param, \array_flip($this->whereFields));

        // let's be atomic while we're at it (and where applicable ...)
        // btw, multi insert are not necessarily that faster in real world
        // situation where there is a lot of updates and you need ot keep
        // atomicity using transactions
        DB::transaction(function () use ($loadQuery, $whereClause, $param) {
            if ($loadQuery->where($whereClause)->sharedLock()->exists()) {
                $update = \array_diff_key($param, $whereClause);
                $loadQuery->update($update);
            } else {
                $loadQuery->insert($param);
            }
        });
    }
}
