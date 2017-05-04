<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl;

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
     * The total amount of record to fetch, in case
     * there is a limit set
     *
     * @var int
     */
    protected $extractLimit;

    /**
     * @var array
     */
    protected $aggregateNodes = [];

    /**
     * @var int
     */
    protected $aggregateNodeIdx = 0;

    /**
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
     * @var array
     */
    protected $reverseAggregateTable = [];

    /**
     * @param int $recordLimit
     *
     * @return $this
     */
    public function setExtractLimit($recordLimit)
    {
        $this->extractLimit = max(1, (int) $recordLimit);

        return $this;
    }

    /**
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
     * @param NodeInterface $node
     *
     * @throws YaEtlException
     */
    public function add(NodeInterface $node)
    {
        throw new YaEtlException('add() is not directly available, use YaEtl grammar from(), transform(), join() and / or to() instead');
    }

    /**
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
     * @staticvar type $flowHashes
     *
     * @param YaEtl $flow
     * @param bool  $isAReturningVal
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
     * kiss method to expose basic stats
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
[YaEtl] %s Joiner - %s Join - %s Branch
[YaEtl] %s Transformer - %s Transform - %s Loader - %s Load - %s Flush
[YaEtl] Time : %s - Memory: %4.2fMiB',
            $this->flowStatus,
            \number_format($stats['num_extractor'], 0, '.', ' '),
            \number_format($stats['num_extract'], 0, '.', ' '),
            \number_format($stats['num_records'], 0, '.', ' '),
            \number_format($this->numIterate, 0, '.', ' '),
            \number_format($stats['num_joiner'], 0, '.', ' '),
            \number_format($stats['num_join'], 0, '.', ' '),
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

        $this->nodes[$nodeIdx]       = $node;
        $this->nodeMap[$nodeHash]    = \array_replace($this->nodeMapDefault, [
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
     * @param array $stats
     *
     * @return array
     */
    protected function processStats($stats)
    {
        if (!empty($stats['nodes'])) {
            $stats['nodes'] = $this->processStats($stats['nodes']);
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
     * calls each WorkFlow's loaders flush method
     *
     * @return $this
     */
    protected function flush()
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof LoaderInterface || \is_a($node, static::class)) {
                $node->flush($this->flowStatus);
                ++$this->stats['num_flush'];
            }
        }

        return $this;
    }
}
