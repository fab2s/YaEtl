<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Qualifiers;

use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\NodalFlowException;

/**
 * Class CallableQualifier
 */
class CallableQualifier extends QualifierAbstract
{
    /**
     * @var callable
     */
    protected $qualifier;

    /**
     * Instantiate a CallableQualifier Node
     *
     * @param callable $qualifier
     *
     * @throws NodalFlowException
     */
    public function __construct(callable $qualifier)
    {
        $this->qualifier = $qualifier;
        parent::__construct();
    }

    /**
     * @param mixed $param
     *
     * @return InterrupterInterface|null|bool `null` do do nothing, eg let the Flow proceed untouched
     *                                        `true` to trigger a continue on the carrier Flow (not ancestors)
     *                                        `false` to trigger a break on the carrier Flow (not ancestors)
     *                                        `InterrupterInterface` to trigger an interrupt to propagate up to a target (which may be one ancestor)
     */
    public function qualify($param)
    {
        return \call_user_func($this->qualifier, $param);
    }
}
