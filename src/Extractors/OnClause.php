<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

/**
 * class OnClause
 */
class OnClause implements OnClauseInterface
{
    /**
     * @var string
     */
    protected $fromKeyAlias;

    /**
     * @var string
     */
    protected $joinKeyAlias;

    /**
     * @var callable
     */
    protected $merger;

    /**
     * @var bool
     */
    protected $leftJoin = false;

    /**
     * the default record to return in left join mode
     * will be set to true in order to break the branch exec
     * in join mode or this value in left join mode
     *
     * @var mixed
     */
    protected $defaultRecord;

    /**
     * @param string     $fromKeyAlias  The from unique key name in record
     * @param string     $joinKeyAlias  The join unique key name in record
     * @param callable   $merger
     * @param null|mixed $defaultRecord null for a regular join,
     *                                  mixed a default record to be
     *                                  used each time there is no match
     *                                  just like a left join would
     *
     * @throws \Exception
     */
    public function __construct($fromKeyAlias, $joinKeyAlias, callable $merger, $defaultRecord = null)
    {
        $this->fromKeyAlias = \trim($fromKeyAlias);
        $this->joinKeyAlias = \trim($joinKeyAlias);

        if ($this->fromKeyAlias === '' || $this->joinKeyAlias === '') {
            throw new \Exception('[YaEtl] From and Join are required');
        }

        $this->merger = $merger;

        if ($defaultRecord !== null) {
            $this->leftJoin      = true;
            $this->defaultRecord = $defaultRecord;
        }
    }

    /**
     * @return string The From extractor unique key name as exposed in each record
     */
    public function getFromKeyAlias()
    {
        return $this->fromKeyAlias;
    }

    /**
     * @return string The Join extractor unique key name as exposed in each record
     */
    public function getJoinKeyAlias()
    {
        return $this->joinKeyAlias;
    }

    /**
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
     * @return bool
     */
    public function isLeftJoin()
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
