<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib;

use fab2s\YaEtl\Extractors\NullExtractor;
use fab2s\YaEtl\Loaders\ArrayLoader;
use fab2s\YaEtl\YaEtl;

/**
 * Class NullExtractorTest
 */
class NullExtractorTest extends TestBase
{
    public function testNullExtractor()
    {
        $arrayLoader   = new ArrayLoader;
        $yaEtl         = new YaEtl;
        $nullExtractor = (new NullExtractor())->setLimit(10);
        $yaEtl->from(clone $nullExtractor)
            ->to($arrayLoader)
            ->exec();

        $expected = array_fill(0, 10, null);
        $this->assertSame($expected, $arrayLoader->getLoadedData());
        $this->assertSame($expected, iterator_to_array($nullExtractor->getTraversable()));
    }
}
