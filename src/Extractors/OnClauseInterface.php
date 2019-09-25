<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

/**
 * Interface OnClauseInterface
 */
interface OnClauseInterface
{
    /**
     * Instantiate an OnClose
     *
     * @param string     $fromKeyAlias  The from unique key name in record
     * @param string     $joinKeyAlias  The join unique key name in record
     * @param callable   $merger
     * @param null|mixed $defaultRecord null for a regular join,
     *                                  mixed a default record to be
     *                                  used each time there is no match
     *                                  just like a left join would
     */
    public function __construct(string $fromKeyAlias, string $joinKeyAlias, callable $merger, $defaultRecord = null);

    /**
     * Get the from key alias
     *
     * @return string The From extractor unique key name as exposed in each record
     */
    public function getFromKeyAlias(): string;

    /**
     * Get the join key alias
     *
     * @return string The Join extractor unique key name as exposed in each record
     */
    public function getJoinKeyAlias(): string;

    /**
     * Merge Joined data into the original record
     *
     * @param mixed $upstreamRecord
     * @param mixed $record
     *
     * @return mixed The somehow merged record
     */
    public function merge($upstreamRecord, $record);

    /**
     * Indicate if we are left joining
     *
     * @return bool
     */
    public function isLeftJoin(): bool;

    /**
     * Get the default record to use when no matching
     * record can be joined in left join mode
     *
     * @return mixed the default record
     */
    public function getDefaultRecord();
}
