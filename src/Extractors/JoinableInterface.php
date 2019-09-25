<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

use fab2s\NodalFlow\Nodes\ExecNodeInterface;

/**
 * Interface JoinableInterface
 * A joinable is an extractor that can be joined against
 * and / or can join against another joinable
 */
interface JoinableInterface extends ExtractorInterface, ExecNodeInterface
{
    /**
     * Generate record map, used to allow joiner to join
     *
     * @param string|null $fromKeyAlias The from unique key to get the map against
     *                                  as exposed in the record
     *
     * @return mixed whatever you need to represent the record collection
     *               Could be something as simple as an array of all
     *               extracted record ids for Joiners to query
     *               against, and know what to do when one of their record
     *               is missing from their own extract collection
     */
    public function getRecordMap(?string $fromKeyAlias = null);

    /**
     * Set the extractor to get record map from
     *
     * @param JoinableInterface $joinFrom
     *
     * @return static
     */
    public function setJoinFrom(self $joinFrom): self;

    /**
     * Set Joiner's ON clause. Only used in Join mode
     *
     * @param OnClauseInterface $onClause
     *
     * @return static
     */
    public function setOnClause(OnClauseInterface $onClause): self;

    /**
     * Get Joiner's ON clause. Only used in Join mode
     *
     * @return OnClauseInterface|null
     */
    public function getOnClause(): ?OnClauseInterface;

    /**
     * exec will join incoming $record with the joined record from its
     * matching extracted record collection
     * exec is supposed to call $this->carrier->continueFlow() when the desired
     * action is to skip record in join mode and return the record with default
     * join values in left join mode
     *
     * @param mixed $record
     *
     * @return mixed the result of the join
     */
    public function exec($record = null);

    /**
     * Register ON clause field mapping. Used by an eventual joiner to this
     * to build relevant recordMap
     *
     * @param OnClauseInterface $onClause
     *
     * @return static
     */
    public function registerJoinerOnClause(OnClauseInterface $onClause): self;
}
