<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

use fab2s\NodalFlow\Nodes\NodeAbstract;

/**
 * Class ExtractorAbstract
 */
abstract class ExtractorAbstract extends NodeAbstract implements ExtractorInterface
{
    /**
     * This is a Traversable
     *
     * @var bool
     */
    protected $isATraversable = true;

    /**
     * This is a returning value
     *
     * @var bool
     */
    protected $isAReturningVal = true;

    /**
     * This is not a Flow
     *
     * @var bool
     */
    protected $isAFlow = false;

    /**
     * @var array
     */
    protected $nodeIncrements = [
        'num_records' => 'num_iterate',
        'num_extract' => 0,
    ];
}
