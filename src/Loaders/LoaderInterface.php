<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Loaders;

use fab2s\NodalFlow\Flows\FlowStatusInterface;
use fab2s\NodalFlow\Nodes\ExecNodeInterface;

/**
 * Interface LoaderInterface
 */
interface LoaderInterface extends ExecNodeInterface
{
    /**
     * This method is called at the end of the workflow in case the loader
     * needs to flush some remaining data from its eventual buffer.
     * Could be used to buffer records in order to perform multi inserts etc ...
     *
     * @param FlowStatusInterface|null $flowStatus the flush status, should only be set by
     *                                             YaEtl to indicate last flush() call status
     *                                             either :
     *                                             - clean (`isClean()`): everything went well
     *                                             - dirty (`isDirty()`): one extractor broke the flow
     *                                             - exception (`isException()`): an exception was raised during the flow
     **/
    public function flush(?FlowStatusInterface $flowStatus = null);
}
