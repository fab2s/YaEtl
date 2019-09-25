<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

/**
 * Interface ExtractorBatchLimitInterface
 */
interface ExtractorBatchLimitInterface extends ExtractorLimitInterface
{
    /**
     * Set Query offset
     * Can be used to set a specific offset prior to start the extraction
     *
     * @param int $offset The query offset
     *
     * @return static
     */
    public function setOffset(int $offset): self;

    /**
     * Get current query offset
     *
     * @return int
     */
    public function getOffset(): int;

    /**
     * Get current batch size
     *
     * @return int
     */
    public function getBatchSize(): int;

    /**
     * Set batch size
     *
     * @param int $batchSize
     *
     * @return static
     */
    public function setBatchSize(int $batchSize): self;

    /**
     * makes sure that offset + batchSize does not exceed limit
     * by setting $this->batchSize to 0 when going beyond $this->limit
     *
     * @return static
     */
    public function enforceBatchSize(): self;
}
