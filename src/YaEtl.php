<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl;

use fab2s\NodalFlow\Flows\FlowEventAbstract;
use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Flows\FlowStatusInterface;
use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\AggregateNodeInterface;
use fab2s\NodalFlow\Nodes\BranchNode;
use fab2s\NodalFlow\Nodes\BranchNodeInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;
use fab2s\NodalFlow\Nodes\TraversableNodeInterface;
use fab2s\NodalFlow\YaEtlException;
use fab2s\YaEtl\Events\YaEtlEvent;
use fab2s\YaEtl\Extractors\AggregateExtractor;
use fab2s\YaEtl\Extractors\ExtractorInterface;
use fab2s\YaEtl\Extractors\JoinableInterface;
use fab2s\YaEtl\Extractors\OnClauseInterface;
use fab2s\YaEtl\Loaders\LoaderInterface;
use fab2s\YaEtl\Qualifiers\QualifierInterface;
use fab2s\YaEtl\Transformers\TransformerInterface;

/**
 * Class YaEtl
 */
class YaEtl extends NodalFlow
{
    /**
     * @var array
     */
    protected $flowIncrements = [
        'num_extract'     => 0,
        'num_extractor'   => 0,
        'num_join'        => 0,
        'num_joiner'      => 0,
        'num_merge'       => 0,
        'num_records'     => 'num_iterate',
        'num_transform'   => 0,
        'num_transformer' => 0,
        'num_qualifier'   => 0,
        'num_qualify'     => 0,
        'num_branch'      => 0,
        'num_load'        => 0,
        'num_loader'      => 0,
        'num_flush'       => 0,
    ];

    /**
     * The revers aggregate lookup table
     *
     * @var array
     */
    protected $reverseAggregateTable = [];

    /**
     * @var bool
     */
    protected $forceFlush = false;

    /**
     * Adds an extractor to the Flow which may be aggregated with another one
     *
     * @param ExtractorInterface      $extractor
     * @param null|ExtractorInterface $aggregateWith Use the extractor instance you want to aggregate with
     *
     * @throws YaEtlException
     * @throws NodalFlowException
     *
     * @return static
     */
    public function from(ExtractorInterface $extractor, ExtractorInterface $aggregateWith = null): self
    {
        if ($aggregateWith !== null) {
            $this->aggregateTo($extractor, $aggregateWith);
        } else {
            parent::add($extractor);
            $this->flowMap->incrementFlow('num_extractor');
        }

        return $this;
    }

    /**
     * @param QualifierInterface $qualifier
     *
     * @throws NodalFlowException
     *
     * @return static
     */
    public function qualify(QualifierInterface $qualifier): self
    {
        parent::add($qualifier);
        $this->flowMap->incrementFlow('num_qualifier');

        return $this;
    }

    /**
     * Override NodalFlow's add method to prohibit its direct usage
     *
     * @param NodeInterface $node
     *
     * @throws YaEtlException
     *
     * @return FlowInterface
     */
    public function add(NodeInterface $node): FlowInterface
    {
        throw new YaEtlException('add() is not directly available, use YaEtl grammar instead');
    }

    /**
     * By default, branched flows will only see their
     * `flush()` method called when the top most parent
     * triggers its own `flush()`.
     * It make sense most of the time to to do so as
     * the most common use case for branches so far is
     * to deal with one record at a time without generating
     * records (even when left joining). In such case,
     * the `flush()` method really need to be called by the flow
     * exactly when the top most flow one is.
     *
     * Set to true if you are generating many records in a branch
     * and it makes sense to flush the branch more often
     * Also note that the branch will also be flushed at the end
     * of its top most parent flow.
     *
     * @param bool $forceFlush
     *
     * @return static
     */
    public function forceFlush(bool $forceFlush): self
    {
        $this->forceFlush = $forceFlush;

        return $this;
    }

    /**
     * Adds a Joiner to a specific Extractor in the FLow
     *
     * @param JoinableInterface $extractor
     * @param JoinableInterface $joinFrom
     * @param OnClauseInterface $onClause
     *
     * @throws NodalFlowException
     *
     * @return static
     */
    public function join(JoinableInterface $extractor, JoinableInterface $joinFrom, OnClauseInterface $onClause): self
    {
        $joinFrom->registerJoinerOnClause($onClause);
        $extractor->setJoinFrom($joinFrom);
        $extractor->setOnClause($onClause);

        parent::add($extractor);
        $this->flowMap->incrementFlow('num_joiner');

        return $this;
    }

    /**
     * Adds a Transformer to the Flow
     *
     * @param TransformerInterface $transformer
     *
     * @throws NodalFlowException
     *
     * @return static
     */
    public function transform(TransformerInterface $transformer): self
    {
        parent::add($transformer);
        $this->flowMap->incrementFlow('num_transformer');

        return $this;
    }

    /**
     * Adds a Loader to the Flow
     *
     * @param LoaderInterface $loader
     *
     * @throws NodalFlowException
     *
     * @return static
     */
    public function to(LoaderInterface $loader): self
    {
        parent::add($loader);
        $this->flowMap->incrementFlow('num_loader');

        return $this;
    }

    /**
     * Adds a Branch (Flow) to the Flow
     *
     * @param YaEtl $flow            The Branch to add in this Flow
     * @param bool  $isAReturningVal To indicate if this Branch Flow is a true Branch or just
     *                               a bag of Nodes to execute at this location of the Flow
     *
     * @throws NodalFlowException
     *
     * @return static
     */
    public function branch(self $flow, $isAReturningVal = false): self
    {
        parent::add(new BranchNode($flow, $isAReturningVal));
        $this->flowMap->incrementFlow('num_branch');

        return $this;
    }

    /**
     * Triggered right after the flow stops
     *
     * @return static
     */
    public function flowEnd(): NodalFlow
    {
        $this->flush();

        parent::flowEnd();

        return $this;
    }

    /**
     * KISS method to expose basic stats
     *
     * @return array<string,integer|string>
     */
    public function getStats(): array
    {
        $stats = parent::getstats();

        $tpl = '[YaEtl]({FLOW_STATUS}) {NUM_EXTRACTOR_TOTAL} Extractor - {NUM_EXTRACT_TOTAL} Extract - {NUM_RECORDS_TOTAL} Record ({NUM_ITERATE_TOTAL} Iterations)
[YaEtl] {NUM_JOINER_TOTAL} Joiner - {NUM_JOIN_TOTAL} Join - {NUM_CONTINUE_TOTAL} Continue - {NUM_BREAK_TOTAL} Break - {NUM_QUALIFIER_TOTAL} Qualifier - {NUM_QUALIFY_TOTAL} Qualify
[YaEtl] {NUM_TRANSFORMER_TOTAL} Transformer - {NUM_TRANSFORM_TOTAL} Transform - {NUM_LOADER_TOTAL} Loader - {NUM_LOAD_TOTAL} Load
[YaEtl] {NUM_BRANCH_TOTAL} Branch - {NUM_CONTINUE_TOTAL} Continue - {NUM_BREAK_TOTAL} Break - {NUM_FLUSH_TOTAL} Flush
[YaEtl] Time : {DURATION} - Memory: {MIB} MiB';

        $vars = [];
        foreach ($this->flowIncrements as $key => $ignore) {
            $stats[$key . '_total'] += $stats[$key];
        }

        foreach ($stats as $varName => $value) {
            if (is_array($value)) {
                continue;
            }

            if (is_numeric($value)) {
                $vars['{' . strtoupper($varName) . '}'] = \number_format($stats[$varName], is_int($value) ? 0 : 2, '.', ' ');
                continue;
            }

            $vars['{' . strtoupper($varName) . '}'] = $value;
        }

        $stats['report'] = str_replace(array_keys($vars), array_values($vars), $tpl);

        return $stats;
    }

    /**
     * Tells if the flow is set to force flush
     * Only used when branched (to tell the parent)
     *
     * @return bool
     */
    public function isForceFlush(): bool
    {
        return !empty($this->forceFlush);
    }

    /**
     * @param string $class
     *
     * @throws \ReflectionException
     *
     * @return static
     */
    protected function initDispatchArgs(string $class): FlowEventAbstract
    {
        parent::initDispatchArgs($class);
        $this->dispatchArgs[$this->eventInstanceKey] = new YaEtlEvent($this);

        return $this;
    }

    /**
     * Used internally to aggregate Extractors
     *
     * @param ExtractorInterface $extractor
     * @param ExtractorInterface $aggregateWith
     *
     * @throws YaEtlException
     * @throws NodalFlowException
     *
     * @return static
     */
    protected function aggregateTo(ExtractorInterface $extractor, ExtractorInterface $aggregateWith): self
    {
        // aggregate with target Node
        $aggregateWithNodeId = $aggregateWith->getId();
        $aggregateWithIdx    = $this->flowMap->getNodeIndex($aggregateWithNodeId);
        if ($aggregateWithIdx === null && !isset($this->reverseAggregateTable[$aggregateWithNodeId])) {
            throw new YaEtlException('Cannot aggregate with orphaned Node:' . \get_class($aggregateWith));
        }

        /** @var TraversableNodeInterface $aggregateWithNode */
        $aggregateWithNode = $this->nodes[$aggregateWithIdx];
        if ($aggregateWithNode instanceof AggregateNodeInterface) {
            $aggregateWithNode->addTraversable($extractor);
            $this->reverseAggregateTable[$extractor->getId()] = $aggregateWithIdx;

            return $this;
        }

        $aggregateNode = new AggregateExtractor(true);
        // keep track of this extractor before we bury it in the aggregate
        $this->reverseAggregateTable[$aggregateWithNode->getId()] = $aggregateWithIdx;
        // now replace its slot in the main tree
        $this->replace($aggregateWithIdx, $aggregateNode);
        $aggregateNode->addTraversable($aggregateWithNode)
            ->addTraversable($extractor);

        // adjust counters as we did remove the $aggregateWith Extractor from this flow
        $reg = &$this->registry->get($this->getId());
        --$reg['flowStats']['num_extractor'];

        // aggregate node did take care of setting carrier
        $this->reverseAggregateTable[$aggregateNode->getId()] = $aggregateWithIdx;
        $this->reverseAggregateTable[$extractor->getId()]     = $aggregateWithIdx;

        return $this;
    }

    /**
     * Calls each WorkFlow's loaders and branch flush method
     *
     * @param FlowStatusInterface|null $flowStatus
     *
     * @return static
     */
    protected function flush(FlowStatusInterface $flowStatus = null): self
    {
        if ($flowStatus === null) {
            if ($this->hasParent() && !$this->isForceFlush()) {
                // we'll get another chance at this
                return $this;
            }

            // use current status
            return $this->flushNodes($this->flowStatus);
        }

        // use parent's status
        return $this->flushNodes($flowStatus);
    }

    /**
     * Actually flush nodes
     *
     * @param FlowStatusInterface $flowStatus
     *
     * @return static
     */
    protected function flushNodes(FlowStatusInterface $flowStatus): self
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof LoaderInterface) {
                $node->flush($flowStatus);
                $this->flowMap->incrementFlow('num_flush');
                $this->triggerEvent(YaEtlEvent::FLOW_FLUSH, $node);
                continue;
            }

            // start with only flushing YaEtl and extends
            if ($node instanceof BranchNodeInterface) {
                $flow = $node->getPayload();
                if (is_a($flow, static::class)) {
                    /* @var YaEtl $flow */
                    $flow->flush($flowStatus);
                }
            }
        }

        return $this;
    }
}
