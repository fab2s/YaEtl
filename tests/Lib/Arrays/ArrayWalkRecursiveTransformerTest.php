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
use fab2s\YaEtl\Transformers\Arrays\ArrayWalkRecursiveTransformer;
use fab2s\YaEtl\YaEtlException;

class ArrayWalkRecursiveTransformerTest extends TestBase
{
    /**
     * @dataProvider arrayWalkRecursiveProvider
     *
     * @param callable $callable
     * @param array    $data
     * @param          $expected
     * @param null     $arg
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function testArrayWalkRecursiveTransformer(callable $callable, array $data, $expected, $arg = null)
    {
        $transformer = new ArrayWalkRecursiveTransformer($callable, $arg);

        $this->assertSame($expected, $transformer->exec($data));
    }

    public function arrayWalkRecursiveProvider(): array
    {
        return [
            [
                function ($value, $key) {
                    $value = "$key:$value";
                },
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'key3' => 'val3',
                    'sub'  => [
                        'subKey1' => 'subVal1',
                        'subKey2' => 'subVal2',
                        'subKey3' => 'subVal3',
                    ],
                ],
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'key3' => 'val3',
                    'sub'  => [
                        'subKey1' => 'subVal1',
                        'subKey2' => 'subVal2',
                        'subKey3' => 'subVal3',
                    ],
                ],
            ],
            [
                function (&$value, $key) {
                    $value = "$key:$value";
                },
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'key3' => 'val3',
                    'sub'  => [
                        'subKey1' => 'subVal1',
                        'subKey2' => 'subVal2',
                        'subKey3' => 'subVal3',
                    ],
                ],
                [
                    'key1' => 'key1:val1',
                    'key2' => 'key2:val2',
                    'key3' => 'key3:val3',
                    'sub'  => [
                        'subKey1' => 'subKey1:subVal1',
                        'subKey2' => 'subKey2:subVal2',
                        'subKey3' => 'subKey3:subVal3',
                    ],
                ],
            ],
            [
                function (&$value, $key, $prefix) {
                    $value = "$prefix:$key:$value";
                },
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'key3' => 'val3',
                    'sub'  => [
                        'subKey1' => 'subVal1',
                        'subKey2' => 'subVal2',
                        'subKey3' => 'subVal3',
                    ],
                ],
                [
                    'key1' => 'prefix:key1:val1',
                    'key2' => 'prefix:key2:val2',
                    'key3' => 'prefix:key3:val3',
                    'sub'  => [
                        'subKey1' => 'prefix:subKey1:subVal1',
                        'subKey2' => 'prefix:subKey2:subVal2',
                        'subKey3' => 'prefix:subKey3:subVal3',
                    ],
                ],
                'prefix',
            ],
        ];
    }
}
