<?php

/*
 * This file is part of YaEtl.
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
     * @var bool
     */
    protected $isATraversable = true;

    /**
     * @var bool
     */
    protected $isAReturningVal = true;

    /**
     * @var bool
     */
    protected $isAFlow = false;

    /**
     * @var int
     */
    protected $numExtract = 0;

    /**
     * Used to gather stats
     *
     * @return int The number of extractions
     */
    public function getNumExtract()
    {
        return $this->numExtract;
    }
}
