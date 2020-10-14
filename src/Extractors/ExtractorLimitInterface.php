<?php

/*
 * This file is part of YaEtl
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
     * Set extract limit
     *
     * @param int|null $limit
     *
     * @return static
     */
    public function setLimit(?int $limit): self;

    /**
     * Get current limit
     *
     * @return int
     */
    public function getLimit(): ?int;

    /**
     * Tells if limit is reached already
     *
     * @return bool true if limit is reached
     */
    public function isLimitReached(): bool;
}
