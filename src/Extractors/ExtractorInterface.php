<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

use fab2s\NodalFlow\Nodes\TraversableNodeInterface;

/**
 * Interface ExtractorInterface
 */
interface ExtractorInterface extends TraversableNodeInterface
{
    /**
     * This method is vaguely similar to a valid() meta iterator
     * It will triggers the record collection extraction when called
     * and return true when records where fetched.
     *
     * This is useful when batch extracting. If your extractor
     * does not perform batch extract (for example if you are
     * just reading a file line by line), just make so this method
     * triggers file open and return true in case of success and false
     * when called again.
     *
     * @param mixed|null $param
     *
     * @return bool false in case no more records can be fetched
     */
    public function extract($param = null): bool;
}
