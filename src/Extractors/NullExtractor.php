<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

/**
 * Class NullExtractor
 */
class NullExtractor extends ExtractorBatchLimitAbstract
{
    /**
     * Total number of records to fetch
     *
     * @var int
     */
    protected $limit = 5000;

    /**
     * Triggers an extract
     *
     * @param mixed $param
     *
     * @return bool
     */
    public function extract($param = null): bool
    {
        if ($this->numRecords >= $this->limit) {
            return false;
        }

        return true;
    }

    /**
     * Return the dumbest Generator ever
     *
     * @param mixed $param
     *
     * @return \Generator
     */
    public function getTraversable($param = null): iterable
    {
        yield null;
    }
}
