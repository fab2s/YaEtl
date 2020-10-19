<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

use fab2s\NodalFlow\NodalFlowException;

/**
 * abstract Class DbExtractorAbstract
 */
abstract class DbExtractorAbstract extends ExtractorBatchLimitAbstract
{
    /**
     * The SQL query
     *
     * @var mixed
     */
    protected $extractQuery;

    /**
     * @var iterable
     */
    protected $extracted;

    /**
     * Instantiate a DB extractor
     *
     * @param mixed $extractQuery
     *
     * @throws NodalFlowException
     */
    public function __construct($extractQuery = null)
    {
        if ($extractQuery !== null) {
            $this->setExtractQuery($extractQuery);
        }

        parent::__construct();
    }

    /**
     * Trigger a batch extract
     *
     * @param mixed $param
     *
     * @return bool
     */
    public function extract($param = null): bool
    {
        if ($this->isLimitReached()) {
            return false;
        }

        $this->enforceBatchSize();
        if ($this->fetchRecords()) {
            $this->incrementOffset();

            return true;
        }

        return false;
    }

    /**
     * Set the Extract SQL query
     *
     * @param mixed $extractQuery
     *
     * @return static
     */
    public function setExtractQuery($extractQuery): self
    {
        $this->extractQuery = $extractQuery;

        return $this;
    }

    /**
     * Build the LIMIT...OFFSET bit of the query
     *
     * @return string
     */
    protected function getLimitOffsetBit(): string
    {
        return ' ' . \implode('', [
            ' LIMIT ' . (int) $this->batchSize,
            $this->offset ? ' OFFSET ' . (int) $this->offset : '',
        ]);
    }

    /**
     * Execute query and store results calling this->setExtracted
     *
     * @return bool true if there are records fetched
     */
    abstract protected function fetchRecords(): bool;
}
