<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Loaders;

use fab2s\NodalFlow\Flows\FlowStatusInterface;
use fab2s\NodalFlow\Nodes\NodeAbstract;

/**
 * Class LoaderAbstract
 */
abstract class LoaderAbstract extends NodeAbstract implements LoaderInterface
{
    /**
     * This is not a traversable
     *
     * @var bool
     */
    protected $isATraversable = false;

    /**
     * Loader can return a value, though it is set
     * to false by default. If you need return values
     * from a loader, set this to true, and next nodes
     * will get the returned value as param.
     *
     * @var bool
     */
    protected $isAReturningVal = false;

    /**
     * This is not a Flow
     *
     * @var bool
     */
    protected $isAFlow = false;

    /**
     * @var array
     */
    protected $nodeIncrements = [
        'num_load' => 'num_exec',
    ];

    /**
     * Implement flush as noOp since it's not always
     * required. Besides, if you do implement it
     * while not performing any type of multi insert,
     * you will take the risk to insert the last record
     * twice if you do not pay attention. Because chances
     * are that flush() will be called each time load()
     * is called, and YaEtl does call flush() after the
     * extract loop ended.
     *
     * @param FlowStatusInterface|null $flowStatus the flush status, should only be set by
     *                                             YaEtl to indicate last flush() call status
     *                                             either :
     *                                             - clean (`isClean()`): everything went well
     *                                             - dirty (`isDirty()`): one extractor broke the flow
     *                                             - exception (`isException()`): an exception was raised during the flow
     **/
    public function flush(?FlowStatusInterface $flowStatus = null)
    {
        /*
         * `if ($flowStatus !== null) {
         *      // YaEtl's call to flush()
         *      if ($flowStatus->isRunning()) {
         *           // flow is running
         *      } elseif ($flowStatus->isClean()) {
         *           // everything went well
         *      } elseif ($flowStatus->isDirty()) {
         *           // a node broke the flow
         *      } elseif ($flowStatus->isException()) {
         *           // an exception was raised during the flow execution
         *      }
         * } else {
         *      // it should be you calling this method
         *      // during the flow execution (multi insert)
         * }`
         */
    }
}
