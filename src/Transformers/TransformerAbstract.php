<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Transformers;

use fab2s\NodalFlow\Nodes\NodeAbstract;

/**
 * Class TransformerAbstract
 */
abstract class TransformerAbstract extends NodeAbstract implements TransformerInterface
{
    /**
     * This is not a traversable
     *
     * @var bool
     */
    protected $isATraversable = false;

    /**
     * This is a returning value
     *
     * @var bool
     */
    protected $isAReturningVal = true;

    /**
     * This is not a FLow
     *
     * @var bool
     */
    protected $isAFlow = false;

    /**
     * @var array
     */
    protected $nodeIncrements = [
        'num_transform' => 'num_exec',
    ];
}
