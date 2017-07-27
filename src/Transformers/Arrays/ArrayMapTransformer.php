<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Transformers\Arrays;

use fab2s\YaEtl\Transformers\TransformerAbstract;

/**
 * Class ArrayMapTransformer
 */
class ArrayMapTransformer extends TransformerAbstract
{
    /**
     * Any callable with one argument which returns something
     *
     * @var callable
     */
    protected $maper;

    /**
     * @param callable $maper
     */
    public function __construct(callable $maper)
    {
        $this->maper = $maper;
    }

    /**
     * Execute the array_map call
     *
     * @param mixed $record
     *
     * @return mixed
     */
    public function exec($record)
    {
        return \array_map($this->maper, $record);
    }
}
