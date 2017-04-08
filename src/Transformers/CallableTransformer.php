<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Transformers;

use fab2s\NodalFlow\Nodes\PayloadNodeAbstract;

/**
 * Class CallableTransformer
 */
class CallableTransformer extends PayloadNodeAbstract implements TransformerInterface
{
    /**
     * @param callable $payload
     */
    public function __construct(callable $payload)
    {
        parent::__construct($payload, true, false);
    }

    /**
     * @param mixed $param the record
     *
     * @return mixed
     */
    public function exec($param)
    {
        return \call_user_func($this->payload, $param);
    }
}
