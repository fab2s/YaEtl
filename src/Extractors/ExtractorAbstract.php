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

    /**
     * @var iterable
     */
    protected $extracted;

    /**
     * @var int
     */
    protected $numExtracts = 0;

    /**
     * @var int
     */
    protected $numRecords = 0;

    /**
     * get the traversable to traverse within the Flow
     *
     * @param mixed $param
     *
     * @return iterable
     */
    public function getTraversable($param = null): iterable
    {
        $this->bootNumExtracts();
        while ($this->extract($param)) {
            ++$this->numExtracts;
            foreach ($this->getExtracted() as $record) {
                ++$this->numRecords;
                yield $record;
            }
        }
    }

    /**
     * Get number of records (at the end of the Flow's execution)
     *
     * @return int
     */
    public function getNumRecords(): int
    {
        return $this->numRecords;
    }

    /**
     * Get number of records (at the end of the Flow's execution)
     *
     * @return int
     */
    public function getNumExtracts(): int
    {
        return $this->numExtracts;
    }

    /**
     * @return $this
     */
    public function bootNumExtracts(): self
    {
        $this->numExtracts = $this->numExtracts ?: 0;
        $this->numRecords  = $this->numRecords ?: 0;
        /** @var ExtractorInterface $this */
        if ($carrier = $this->getCarrier()) {
            $nodeMap                = &$carrier->getFlowMap()->getNodeStat($this->getId());
            $nodeMap['num_extract'] = &$this->numExtracts;
        }

        return $this;
    }

    /**
     * return what was extracted during last call to extract
     * As single record must be a collection of one record
     * it can be more elegant to:
     * `    yield $record;`
     * rather than to:
     * `    return [$record];`
     *
     * @return iterable
     */
    protected function getExtracted(): iterable
    {
        return $this->extracted ?: [];
    }

    /**
     * set current extraction result
     *
     * @param iterable|null $extracted
     *
     * @return static
     */
    protected function setExtractedCollection(?iterable $extracted = null): self
    {
        $this->extracted = $extracted;

        return $this;
    }

    /**
     * @param mixed|null $extracted
     *
     * @return static
     */
    protected function setExtractedRecord($extracted = null): self
    {
        $this->extracted = $extracted !== null ? [$extracted] : null;

        return $this;
    }
}
