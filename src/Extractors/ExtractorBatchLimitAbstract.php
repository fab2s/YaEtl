<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

/**
 * Class ExtractorBatchLimitAbstract
 */
abstract class ExtractorBatchLimitAbstract extends ExtractorLimitAbstract implements ExtractorBatchLimitInterface
{
    /**
     * The current offset
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * The start offset
     *
     * @var int
     */
    protected $startOffset = 0;

    /**
     * Number of records to fetch at once
     *
     * @var int
     */
    protected $batchSize = 1337;

    /**
     * makes sure that offset + batchSize does not exceed limit
     * by setting $this->batchSize to 0 when going beyond $this->limit
     *
     * @return static
     */
    public function enforceBatchSize(): ExtractorBatchLimitInterface
    {
        if ($this->limit && ($this->numRecords + (int) $this->batchSize > $this->limit)) {
            $this->batchSize = max(0, $this->limit - $this->numRecords);
        }

        return $this;
    }

    /**
     * can be used to set a specific offset prior to start the scan
     *
     * @param int $offset
     *
     * @return static
     */
    public function setOffset(int $offset): ExtractorBatchLimitInterface
    {
        $this->startOffset = max(0, $offset);

        return $this;
    }

    /**
     * Get query offset
     *
     * @return int
     */
    public function getOffset(): int
    {
        return (int) $this->offset;
    }

    /**
     * Set batch size
     *
     * @param int $batchSize
     *
     * @return static
     */
    public function setBatchSize(int $batchSize): ExtractorBatchLimitInterface
    {
        $this->batchSize = max(1, (int) $batchSize);

        return $this;
    }

    /**
     * Get batch size
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * Adds limit to offset, to be invoked
     * each time extract() is executed
     *
     * @return static
     */
    public function incrementOffset(): self
    {
        $this->offset += (int) $this->batchSize;

        return $this;
    }

    /**
     * @return ExtractorAbstract
     */
    public function bootNumExtracts(): ExtractorAbstract
    {
        // reset pagination each time we trigger
        // an extract (one or more time per flow)
        $this->offset = $this->startOffset ?: 0;

        return parent::bootNumExtracts();
    }
}
