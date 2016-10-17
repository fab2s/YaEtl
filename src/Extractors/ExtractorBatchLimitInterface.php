<?php

/*
 * This file is part of YaEtl.
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
     * can be used to set a specific offset prior to start the extraction
     *
     * @param int $offset
     *
     * @return $this
     */
    public function setOffset($offset);

    /**
     * @return int
     */
    public function getOffset();

    /**
     * @return int
     */
    public function getBatchSize();

    /**
     * @param int $batchSize
     *
     * @return $this
     */
    public function setBatchSize($batchSize);
}
