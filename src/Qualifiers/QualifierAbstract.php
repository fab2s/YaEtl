<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Qualifiers;

use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\Nodes\NodeAbstract;

/**
 * Interface QualifierInterface
 */
abstract class QualifierAbstract extends NodeAbstract implements QualifierInterface
{
    /**
     * Indicate if this Node is traversable
     *
     * @var bool
     */
    protected $isATraversable = false;

    /**
     * Indicate if this Node is returning a value
     *
     * @var bool
     */
    protected $isAReturningVal = false;

    /**
     * Indicate if this Node is a Flow (Branch)
     *
     * @var bool
     */
    protected $isAFlow = false;

    /**
     * The CallableInterruptNode's payload interface is simple :
     *      - return false to break
     *      - return true to continue
     *      - return void|null (whatever) to proceed with the flow
     *
     * @param mixed $param
     *
     * @return mixed|void
     */
    public function exec($param)
    {
        $flowInterrupt = $this->qualify($param);
        if ($flowInterrupt === null) {
            // do nothing, let the flow proceed
            return;
        }

        if ($flowInterrupt instanceof  InterrupterInterface) {
            $flowInterruptType = $flowInterrupt->getType();
        } elseif ($flowInterrupt) {
            $flowInterruptType = InterrupterInterface::TYPE_CONTINUE;
            $flowInterrupt     = null;
        } else {
            $flowInterruptType = InterrupterInterface::TYPE_BREAK;
            $flowInterrupt     = null;
        }

        $this->carrier->interruptFlow($flowInterruptType, $flowInterrupt);
    }
}
