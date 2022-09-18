<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib;

use fab2s\YaEtl\Extractors\OnClause;
use fab2s\YaEtl\YaEtlException;

/**
 * Class OnCloseTest
 */
class OnCloseTest extends TestBase
{
    public function testNewException()
    {
        $this->expectException(YaEtlException::class);
        new OnClause('', 'key', 'trim');
    }
}
