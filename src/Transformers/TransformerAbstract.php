<?php

/*
 * This file is part of YaEtl.
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
     * @var bool
     */
    protected $isATraversable = false;

    /**
     * @var bool
     */
    protected $isAReturningVal = true;

    /**
     * @var bool
     */
    protected $isAFlow = false;
}
