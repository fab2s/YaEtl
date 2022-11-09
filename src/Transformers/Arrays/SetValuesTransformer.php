<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Transformers\Arrays;

use fab2s\YaEtl\Transformers\TransformerAbstract;

class SetValuesTransformer extends TransformerAbstract
{
    /**
     * @var array<string,mixed>
     */
    protected $setUp = [];

    public function __construct(array $setup)
    {
        parent::__construct();
        $this->setUp = $setup;
    }

    public function exec($param = null)
    {
        return \array_replace($param, $this->setUp);
    }
}
