<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Transformers\Arrays;

use fab2s\Strings\Strings;

trait CharsetTransformerTrait
{
    public function getConvertClosure(?string $from = null, string $to = Strings::ENCODING): \Closure
    {
        return function ($value) use ($from, $to) {
            return is_string($value) ? Strings::convert($value, $from, $to) : $value;
        };
    }
}
