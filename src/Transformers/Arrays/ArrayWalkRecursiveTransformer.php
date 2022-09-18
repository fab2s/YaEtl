<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Transformers\Arrays;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Transformers\TransformerAbstract;

/**
 * Class ArrayWalkRecursiveTransformer
 */
class ArrayWalkRecursiveTransformer extends TransformerAbstract
{
    /**
     * Any callable with two argument which returns something
     *
     * @var callable
     */
    protected $callable;

    /**
     * @var mixed
     */
    protected $userData;

    /**
     * @param callable   $callable Worth nothing to say that the first callback argument should
     *                             be a reference if you want anything to append to the record
     * @param null|mixed $userData
     *
     * @throws NodalFlowException
     */
    public function __construct(callable $callable, $userData = null)
    {
        $this->callable = $callable;
        $this->userData = $userData;
        parent::__construct();
    }

    /**
     * Execute the array_map call
     *
     * @param array $record
     *
     * @return array
     */
    public function exec($record = null)
    {
        \array_walk_recursive($record, $this->callable, $this->userData);

        return $record;
    }
}
