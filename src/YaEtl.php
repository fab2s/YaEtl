<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl;

use fab2s\NodalFlow\Flows\FlowStatusInterface;
use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\Nodes\AggregateNode;
use fab2s\NodalFlow\Nodes\AggregateNodeInterface;
use fab2s\NodalFlow\Nodes\BranchNode;
use fab2s\NodalFlow\Nodes\NodeInterface;
use fab2s\NodalFlow\YaEtlException;
use fab2s\YaEtl\Extractors\ExtractorInterface;
use fab2s\YaEtl\Extractors\JoinableInterface;
use fab2s\YaEtl\Extractors\OnClauseInterface;
use fab2s\YaEtl\Loaders\LoaderInterface;
use fab2s\YaEtl\Transformers\TransformerInterface;

/**
 * Class YaEtl
 */
class YaEtl extends NodalFlow
{
    /**
     * The stats items added to NodalFlow's ones
     *
     * @var array
     */
    protected $stats = [
        'start'           => null,
        'end'             => null,
        'duration'        => null,
        'mib'             => null,
        'report'          => '',
        'num_extract'     => 0,
        'num_extractor'   => 0,
        'num_join'        => 0,
        'num_joiner'      => 0,
        'num_merge'       => 0,
        'num_records'     => 0,
        'num_transform'   => 0,
        'num_transformer' => 0,
        'num_branch'      => 0,
        'num_load'        => 0,
        'num_loader'      => 0,
        'num_flush'       => 0,
        'invocations'     => [],
        'nodes'           => [],
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
     * @param null|ExtractorInterface $aggregateWith Use the extractore instance you want to aggregate with
     *
     * @return $this
     */
    public function from(ExtractorInterface $extractor, ExtractorInterface $aggregateWith = null)
    {
        $this->enforceNodeInstanceUnicity($extractor);
        if ($aggregateWith !== null) {
            $this->aggregateTo($extractor, $aggregateWith);
        } else {
            parent::add($extractor);
        }

        ++$this->stats['num_extractor'];

        return $this;
    }

    /**
     * Override NodalFlow's add method to prohibit its direct usage
     *
     * @param NodeInterface $node
     *
     * @throws YaEtlException
     */
    public function add(NodeInterface $node)
    {
        throw new YaEtlException('add() is not directly available, use YaEtl grammar from(), transform(), join() and / or to() instead');
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
     * of its top most parent.
     *
     * @param bool $forceFlush
     *
     * @return $this
     */
    public function forceFlush($forceFlush)
    {
        $this->forceFlush = (bool) $forceFlush;

        return $this;
    }

    /**
     * Adds a Joiner to a specific Extractor in the FLow
     *
     * @param JoinableInterface $extractor
     * @param JoinableInterface $joinFrom
     * @param OnClauseInterface $onClause
     *
     * @return $this
     */
    public function join(JoinableInterface $extractor, JoinableInterface $joinFrom, OnClauseInterface $onClause)
    {
        $this->enforceNodeInstanceUnicity($extractor);
        $joinFrom->registerJoinerOnClause($onClause);
        $extractor->setJoinFrom($joinFrom);
        $extractor->setOnClause($onClause);

        parent::add($extractor);
        ++$this->stats['num_joiner'];

        return $this;
    }

    /**
     * Adds a Transformer to the Flow
     *
     * @param TransformerInterface $transformer
     *
     * @return $this
     */
    public function transform(TransformerInterface $transformer)
    {
        $this->enforceNodeInstanceUnicity($transformer);
        parent::add($transformer);
        ++$this->stats['num_transformer'];

        return $this;
    }

    /**
     * Adds a Loader to the Flow
     *
     * @param LoaderInterface $loader
     *
     * @return $this
     */
    public function to(LoaderInterface $loader)
    {
        $this->enforceNodeInstanceUnicity($loader);
        parent::add($loader);
        ++$this->stats['num_loader'];

        return $this;
    }

    /**
     * Adds a Branch (Flow) to the Flow
     *
     * @staticvar type $flowHashes
     *
     * @param YaEtl $flow            The Branch to add in this Flow
     * @param bool  $isAReturningVal To indicate if this Branch Flow is a true Branch or just
     *                               a bag of Nodes to execute at this location of the Flow
     *
     * @throws YaEtlException
     *
     * @return $this
     */
    public function branch(YaEtl $flow, $isAReturningVal = false)
    {
        static $flowHashes;
        if (!isset($flowHashes)) {
            $flowHashes = [
                $this->objectHash($this) => 1,
            ];
        }

        $flowHash = $this->objectHash($flow);
        if (isset($flowHashes[$flowHash])) {
            throw new YaEtlException('An instance of ' . \get_class($flow) . ' appears to be already in use in this flow. Please clone / re new before reuse');
        }

        $flowHashes[$flowHash] = 1;

        parent::add(new BranchNode($flow, $isAReturningVal));
        ++$this->stats['num_branch'];

        return $this;
    }

    /**
     * Triggered right after the flow stops
     *
     * @return $this
     */
    public function flowEnd()
    {
        $this->flush();

        parent::flowEnd();

        return $this;
    }

    /**
     * KISS method to expose basic stats
     *
     * @return array
     */
    public function getStats()
    {
        $stats          = $this->processStats(parent::getstats());
        $stats['nodes'] = $this->getNodeStats();

        $this->collectNodeStats($stats);

        $stats['duration'] = $stats['end'] - $stats['start'];
        $stats             = \array_replace($stats, $this->duration($stats['duration']));
        $stats['report']   = \sprintf(
            '[YaEtl](%s) %s Extractor - %s Extract - %s Record (%s Iterations)
[YaEtl] %s Joiner - %s Join - %s Continue - %s Break - %s Branch
[YaEtl] %s Transformer - %s Transform - %s Loader - %s Load - %s Flush
[YaEtl] Time : %s - Memory: %4.2fMiB',
            $this->flowStatus,
            \number_format($stats['num_extractor'], 0, '.', ' '),
            \number_format($stats['num_extract'], 0, '.', ' '),
            \number_format($stats['num_records'], 0, '.', ' '),
            \number_format($this->numIterate, 0, '.', ' '),
            \number_format($stats['num_joiner'], 0, '.', ' '),
            \number_format($stats['num_join'], 0, '.', ' '),
            \number_format($this->numContinue, 0, '.', ' '),
            \number_format($this->numBreak, 0, '.', ' '),
            \number_format($stats['num_branch'], 0, '.', ' '),
            \number_format($stats['num_transformer'], 0, '.', ' '),
            \number_format($stats['num_transform'], 0, '.', ' '),
            \number_format($stats['num_loader'], 0, '.', ' '),
            \number_format($stats['num_load'], 0, '.', ' '),
            \number_format($stats['num_flush'], 0, '.', ' '),
            $stats['durationStr'],
            $stats['mib']
        );

        return $stats;
    }

    /**
     * Tells if the flow is set to force flush
     * Only used when branched (to tell the parent)
     *
     * @return bool
     */
    public function isForceFlush()
    {
        return !empty($this->forceFlush);
    }

    /**
     * Used internally to aggregate Extracors
     *
     * @param ExtractorInterface $extractor
     * @param ExtractorInterface $aggregateWith
     *
     * @throws YaEtlException
     *
     * @return $this
     */
    protected function aggregateTo(ExtractorInterface $extractor, ExtractorInterface $aggregateWith)
    {
        // aggregate with target Node
        $nodeHash = $aggregateWith->getNodeHash();
        if (!isset($this->nodeMap[$nodeHash]) && !isset($this->reverseAggregateTable[$nodeHash])) {
            throw new YaEtlException('Cannot aggregate with orphaned Node:' . \get_class($aggregateWith));
        }

        $aggregateWithIdx = isset($this->nodeMap[$nodeHash]) ? $this->nodeMap[$nodeHash]['index'] : $this->reverseAggregateTable[$nodeHash];
        if ($this->nodes[$aggregateWithIdx] instanceof AggregateNodeInterface) {
            $this->nodes[$aggregateWithIdx]->addTraversable($extractor);
            // aggregate node did take care of setting carrier and hash
            $this->reverseAggregateTable[$extractor->getNodeHash()] = $aggregateWithIdx;

            return $this;
        }

        $aggregateNode = new AggregateNode(true);
        $aggregateNode->addTraversable($this->nodes[$aggregateWithIdx])
                ->addTraversable($extractor);
        // keep track of this extractor before we burry it in the aggregate
        $this->reverseAggregateTable[$this->nodes[$aggregateWithIdx]->getNodeHash()] = $aggregateWithIdx;
        // now replace its slot in the main tree
        $this->replace($aggregateWithIdx, $aggregateNode);
        // aggregate node did take care of setting carrier and hash
        $this->reverseAggregateTable[$aggregateNode->getNodeHash()]                  = $aggregateWithIdx;
        $this->reverseAggregateTable[$extractor->getNodeHash()]                      = $aggregateWithIdx;

        return $this;
    }

    /**
     * Collect Nodes stats
     *
     * @param array $stats
     *
     * @return $this
     */
    protected function collectNodeStats(array &$stats)
    {
        $stats = \array_replace($this->statsDefault, $stats);
        foreach ($this->nodes as $nodeIdx => $node) {
            if (($node instanceof JoinableInterface) && $node->getOnClause()) {
                $this->nodeStats[$nodeIdx]['num_join'] = $node->getNumRecords();
                $stats['num_join'] += $this->nodeStats[$nodeIdx]['num_join'];
            } elseif ($node instanceof ExtractorInterface) {
                $this->nodeStats[$nodeIdx]['num_records'] = $this->nodeStats[$nodeIdx]['num_iterate'];
                $this->nodeStats[$nodeIdx]['num_extract'] = $node->getNumExtract();
                $stats['num_records'] += $this->nodeStats[$nodeIdx]['num_iterate'];
                $stats['num_extract'] += $this->nodeStats[$nodeIdx]['num_extract'];
            } elseif ($node instanceof TransformerInterface) {
                $this->nodeStats[$nodeIdx]['num_transform'] = $this->nodeStats[$nodeIdx]['num_exec'];
                $stats['num_transform'] += $this->nodeStats[$nodeIdx]['num_transform'];
            } elseif ($node instanceof LoaderInterface) {
                $this->nodeStats[$nodeIdx]['num_load'] = $this->nodeStats[$nodeIdx]['num_exec'];
                $stats['num_load'] += $this->nodeStats[$nodeIdx]['num_load'];
            } elseif ($node instanceof AggregateNodeInterface) {
                $this->nodeStats[$nodeIdx]['num_records'] = $this->nodeStats[$nodeIdx]['num_iterate'];
                $stats['num_records'] += $this->nodeStats[$nodeIdx]['num_iterate'];
                $this->nodeStats[$nodeIdx]['num_extract'] = 0;
                foreach ($node->getNodeCollection() as $extractorNode) {
                    $this->nodeStats[$nodeIdx]['num_extract'] += $extractorNode->getNumExtract();
                }

                $stats['num_extract'] += $this->nodeStats[$nodeIdx]['num_extract'];
            }
        }

        return $this;
    }

    /**
     * Replaces a node with another one
     *
     * @param type          $nodeIdx
     * @param NodeInterface $node
     *
     * @throws YaEtlException
     *
     * @return $this
     */
    protected function replace($nodeIdx, NodeInterface $node)
    {
        if (!isset($this->nodes[$nodeIdx])) {
            throw new YaEtlException('Argument 1 should be a valid index in nodes, got:' . \gettype($nodeIdx));
        }

        unset($this->nodeMap[$this->nodeStats[$nodeIdx]['hash']], $this->nodeStats[$nodeIdx]);
        $nodeHash = $this->objectHash($node);

        $node->setCarrier($this)->setNodeHash($nodeHash);

        $this->nodes[$nodeIdx]    = $node;
        $this->nodeMap[$nodeHash] = \array_replace($this->nodeMapDefault, [
            'class'    => \get_class($node),
            'branchId' => $this->flowId,
            'hash'     => $nodeHash,
            'index'    => $nodeIdx,
        ]);

        // register references to nodeStats to increment faster
        // nodeStats can also be used as reverse lookup table
        $this->nodeStats[$nodeIdx] = &$this->nodeMap[$nodeHash];

        return $this;
    }

    /**
     * Compute final stats
     *
     * @param array $stats
     *
     * @return array
     */
    protected function processStats($stats)
    {
        if (!empty($stats['nodes'])) {
            $stats['nodes'] = $this->processStats($stats['nodes']);
        }

        if (empty($stats['invocations'])) {
            return $stats;
        }

        foreach ($stats['invocations'] as &$value) {
            $value           = \array_replace($value, $this->duration($value['duration']));
            $value['report'] = \sprintf('[YaEtl] Time : %s - Memory: %4.2fMiB',
                $value['durationStr'],
                $value['mib']
            );
        }

        return $stats;
    }

    /**
     * It could lead to really tricky situation if we where to
     * allow multiple instances of the same node. It's obviously
     * wrong with an Extractor, but even a Transformer could
     * create dark corner cases.
     *
     * @param NodeInterface $node
     *
     * @throws YaEtlException
     *
     * @return $this
     */
    protected function enforceNodeInstanceUnicity(NodeInterface $node)
    {
        if ($this->findNodeHashInMap($this->objectHash($node), $this->getNodeMap())) {
            throw new YaEtlException('This instance of ' . \get_class($node) . ' appears to be already in use in this flow. Please deep clone / re new before reuse');
        }

        return $this;
    }

    /**
     * Find a Node by its hash in a nodemap, used to enfore Node instance unicity
     *
     * @param string $hash
     * @param array  $nodeMap
     *
     * @return bool
     */
    protected function findNodeHashInMap($hash, $nodeMap)
    {
        if (isset($nodeMap[$hash])) {
            return true;
        }

        foreach ($nodeMap as $mapData) {
            if (
                !empty($mapData['nodes']) &&
                $this->findNodeHashInMap($hash, $mapData['nodes'])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calls each WorkFlow's loaders and branch flush method
     *
     * @return $this
     */
    protected function flush(FlowStatusInterface $flowStatus = null)
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
     * @return $this
     */
    protected function flushNodes(FlowStatusInterface $flowStatus)
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof LoaderInterface) {
                $node->flush($flowStatus);
                ++$this->stats['num_flush'];
                continue;
            }

            // start with only flushing YaEtl and extends
            if ($node instanceof BranchNode && \is_a($node->getPayload(), static::class)) {
                $node->getPayload()->flush($flowStatus);
                ++$this->stats['num_flush'];
            }
        }

        return $this;
    }
}
