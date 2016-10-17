<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

/**
 * Interface ExtractorLimitInterface
 */
interface ExtractorLimitInterface extends ExtractorInterface
{
    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit($limit);

    /**
     * @return int
     */
    public function getLimit();

    /**
     * @return int
     */
    public function getNumRecords();

    /**
     * @return bool true if limit is reached
     */
    public function isLimitReached();
}
