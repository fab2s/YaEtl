<?php

/*
 * This file is part of YaEtl.
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
     * @param mixed $param
     *
     * @return bool
     */
    public function extract($param = null)
    {
        if ($this->numRecords >= $this->limit) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $param
     */
    public function getTraversable($param)
    {
        yield null;
    }
}
