<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Transformers\Arrays;

use fab2s\Strings\Strings;

class CharsetRecursiveTransformer extends ArrayMapRecursiveTransformer
{
    use CharsetTransformerTrait;

    public function __construct(?string $from = null, string $to = Strings::ENCODING)
    {
        parent::__construct($this->getConvertClosure($from, $to));
    }
}
