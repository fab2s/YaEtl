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
 * Class ArrayMapRecursiveTransformer
 */
class ArrayMapRecursiveTransformer extends TransformerAbstract
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
     * Execute the arrayMapRecursive call
     *
     * @param array $record
     *
     * @return array
     */
    public function exec($record = null)
    {
        return $this->arrayMapRecursive($record);
    }

    protected function arrayMapRecursive(array $record): array
    {
        $out = [];
        foreach ($record as $key => $value) {
            $out[$key] = is_array($value) ? $this->arrayMapRecursive($value) : call_user_func($this->mapper, $value);
        }

        return $out;
    }
}
