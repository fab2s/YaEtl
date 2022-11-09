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

class ArrayKeyTransformer extends TransformerAbstract
{
    /**
     * Any callable with one argument which returns something
     *
     * @var callable
     */
    protected $mapper;

    /**
     * @param callable $mapper
     *
     * @throws NodalFlowException
     */
    public function __construct(callable $mapper)
    {
        $this->mapper = $mapper;
        parent::__construct();
    }

    /**
     * Execute the array_map call
     *
     * @param array $param
     *
     * @return array
     */
    public function exec($param = null)
    {
        return array_combine(array_map($this->mapper, array_keys($param)), array_values($param));
    }
}
