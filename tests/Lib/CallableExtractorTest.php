<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Extractors\CallableExtractor;
use fab2s\YaEtl\Loaders\ArrayLoader;
use fab2s\YaEtl\YaEtl;

/**
 * Class CallableExtractorTest
 */
class CallableExtractorTest extends TestBase
{
    /**
     * @dataProvider callableExtractorProvider
     *
     * @param callable $callable
     * @param          $expected
     *
     * @throws NodalFlowException
     */
    public function testCallableExtractor(callable $callable, $expected)
    {
        $arrayLoader = new ArrayLoader;
        (new YaEtl)
            ->from(new CallableExtractor($callable))
            ->to($arrayLoader)
            ->exec();
        $this->assertSame($expected, $arrayLoader->getLoadedData());
    }

    public function callableExtractorProvider(): array
    {
        return [
            [
                $this->getTraversableClosure(10),
                range(1, 10),
            ],
            [
                function () {
                    return 'notIterable';
                },
                [],
            ],
        ];
    }
}
