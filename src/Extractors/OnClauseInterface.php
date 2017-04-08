<?php

/*
 * This file is part of YaEtl.
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
     * @param string     $fromKeyAlias  The from unique key name in record
     * @param string     $joinKeyAlias  The join unique key name in record
     * @param callable   $merger
     * @param null|mixed $defaultRecord null for a regular join,
     *                                  mixed a default record to be
     *                                  used each time there is no match
     *                                  just like a left join would
     */
    public function __construct($fromKeyAlias, $joinKeyAlias, callable $merger, $defaultRecord = null);

    /**
     * @return string The From extractor unique key name as exposed in each record
     */
    public function getFromKeyAlias();

    /**
     * @return string The Join extractor unique key name as exposed in each record
     */
    public function getJoinKeyAlias();

    /**
     * @param mixed $upstreamRecord
     * @param mixed $record
     *
     * @return mixed The somehow merged record
     */
    public function merge($upstreamRecord, $record);

    /**
     * @return bool
     */
    public function isLeftJoin();

    /**
     * Get the default record to use when no matching
     * record can be joined in left join mode
     *
     * @return mixed the default record
     */
    public function getDefaultRecord();
}
