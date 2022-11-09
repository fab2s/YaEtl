<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib\Arrays;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\Tests\Lib\TestBase;
use fab2s\YaEtl\Transformers\Arrays\ArrayKeyTransformer;

class ArrayKeyTransformerTest extends TestBase
{
    /**
     * @dataProvider arrayKeyProvider
     *
     * @param callable $callable
     * @param array    $data
     * @param array    $expected
     *
     * @throws NodalFlowException
     */
    public function testArrayKeyTransformer(callable $callable, array $data, array $expected)
    {
        $transformer = new ArrayKeyTransformer($callable);

        $this->assertSame($expected, $transformer->exec($data));
    }

    public function arrayKeyProvider(): array
    {
        return [
            [
                'strtolower',
                [
                    'Key1' => 'Val1',
                    'Key2' => 'Val2',
                    'Key3' => 'Val3',
                ],
                [
                    'key1' => 'Val1',
                    'key2' => 'Val2',
                    'key3' => 'Val3',
                ],
            ],
        ];
    }
}
