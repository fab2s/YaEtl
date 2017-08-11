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
 * Class KeyUnsetTransformer
 */
class KeyUnsetTransformer extends TransformerAbstract
{
    /**
     * array of key to unset
     *
     * @var array
     */
    protected $unsetList;

    /**
     * @param array $unsetList array of key to unset
     */
    public function __construct(array $unsetList)
    {
        $this->unsetList = \array_unique($unsetList);
    }

    /**
     * Unsets keys in array
     *
     * @param mixed $record
     *
     * @return mixed
     */
    public function exec($record)
    {
        foreach ($this->unsetList as $keyName) {
            unset($record[$keyName]);
        }

        return $record;
    }
}
