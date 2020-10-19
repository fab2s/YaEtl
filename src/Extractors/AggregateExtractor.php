<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\AggregateNode;
use fab2s\NodalFlow\Nodes\AggregateNodeInterface;
use fab2s\NodalFlow\Nodes\PayloadNodeAbstract;
use fab2s\NodalFlow\Nodes\TraversableNodeInterface;
use fab2s\NodalFlow\YaEtlException;
use fab2s\YaEtl\YaEtl;

/**
 * class AggregateExtractor
 */
class AggregateExtractor extends AggregateNode
{
    /**
     * The underlying pseudo Flow
     *
     * @var YaEtl
     */
    protected $payload;

    /**
     * AggregateExtractor constructor.
     *
     * @param bool $isAReturningVal
     *
     * @throws NodalFlowException
     */
    public function __construct(bool $isAReturningVal)
    {
        // bypass parent, go to grand pa'. DRY won over KISS
        PayloadNodeAbstract::/* @scrutinizer ignore-call */__construct(new YaEtl, $isAReturningVal);
        $this->isATraversable = true;
    }

    /**
     * @param TraversableNodeInterface $node
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     *
     * @return static
     */
    public function addTraversable(TraversableNodeInterface $node): AggregateNodeInterface
    {
        if (!($node instanceof ExtractorInterface)) {
            throw new YaEtlException('AggregateExtractor only supports ExtractorInterface Nodes');
        }

        $this->payload->from($node);

        return $this;
    }
}
