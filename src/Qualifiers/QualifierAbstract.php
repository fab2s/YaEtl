<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Qualifiers;

use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\Nodes\NodeAbstract;
use fab2s\NodalFlow\YaEtlException;

/**
 * Abstract Class QualifierAbstract
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
     * @var array
     */
    protected $nodeIncrements = [
        'num_qualify' => 'num_exec',
    ];

    /**
     * The qualify's method interface is simple :
     *      - return true to qualify the record, that is to use it
     *      - return false|null|void to skip the record
     *      - return InterrupterInterface to leverage complete interruption features
     *
     * @param mixed $param
     *
     * @throws YaEtlException
     *
     * @return mixed|void
     */
    public function exec($param = null)
    {
        $qualifies = $this->qualify($param);
        if ($qualifies === true) {
            return;
        }

        if (empty($qualifies)) {
            $this->carrier->interruptFlow(InterrupterInterface::TYPE_CONTINUE);

            return;
        }

        if ($qualifies instanceof  InterrupterInterface) {
            $this->carrier->interruptFlow($qualifies->getType(), $qualifies);

            return;
        }

        throw new YaEtlException('Qualifier returned wrong type, only Boolean and InterrupterInterface are allowed');
    }
}
