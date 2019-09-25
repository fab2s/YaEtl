<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

use fab2s\NodalFlow\YaEtlException;

/**
 * class OnClause
 */
class OnClause implements OnClauseInterface
{
    /**
     * The single unique key alias as exposed in the original extractor's records
     *
     * @var string
     */
    protected $fromKeyAlias;

    /**
     * The single unique key alias we are joining on
     *
     * @var string
     */
    protected $joinKeyAlias;

    /**
     * The merger to use to merge joined data to the record
     *
     * @var callable
     */
    protected $merger;

    /**
     * Indicate if we are left or just joining
     *
     * @var bool
     */
    protected $leftJoin = false;

    /**
     * The default record to return in left join mode
     * will be set to true in order to break the branch exec
     * in join mode or this value in left join mode
     *
     * @var mixed
     */
    protected $defaultRecord;

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
     *
     * @throws YaEtlException
     */
    public function __construct(string $fromKeyAlias, string $joinKeyAlias, callable $merger, $defaultRecord = null)
    {
        $this->fromKeyAlias = \trim($fromKeyAlias);
        $this->joinKeyAlias = \trim($joinKeyAlias);

        if ($this->fromKeyAlias === '' || $this->joinKeyAlias === '') {
            throw new YaEtlException('From and Join are required');
        }

        $this->merger = $merger;

        if ($defaultRecord !== null) {
            $this->leftJoin      = true;
            $this->defaultRecord = $defaultRecord;
        }
    }

    /**
     * Get the from key alias
     *
     * @return string The From extractor unique key name as exposed in each record
     */
    public function getFromKeyAlias(): string
    {
        return $this->fromKeyAlias;
    }

    /**
     * Get the join key alias
     *
     * @return string The Join extractor unique key name as exposed in each record
     */
    public function getJoinKeyAlias(): string
    {
        return $this->joinKeyAlias;
    }

    /**
     * Merge Joined data into the original record
     *
     * @param mixed $upstreamRecord
     * @param mixed $record
     *
     * @return mixed The somehow merged record
     */
    public function merge($upstreamRecord, $record)
    {
        // don't worry too much @SEE https://github.com/fab2s/call_user_func
        return \call_user_func($this->merger, $upstreamRecord, $record);
    }

    /**
     * Indicate if we are left joining
     *
     * @return bool
     */
    public function isLeftJoin(): bool
    {
        return $this->leftJoin;
    }

    /**
     * Get the default record to use when no matching
     * record can be joined in left join mode
     *
     * @return mixed the default record
     */
    public function getDefaultRecord()
    {
        return $this->defaultRecord;
    }
}
