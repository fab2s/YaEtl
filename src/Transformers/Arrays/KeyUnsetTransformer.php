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
     *
     * @throws NodalFlowException
     */
    public function __construct(array $unsetList)
    {
        $this->unsetList = \array_unique($unsetList);
        parent::__construct();
    }

    /**
     * Unsets keys in array
     *
     * @param array $record
     *
     * @return array
     */
    public function exec($record = null)
    {
        foreach ($this->unsetList as $keyName) {
            unset($record[$keyName]);
        }

        return $record;
    }
}
