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
use fab2s\YaEtl\Transformers\Arrays\ArrayMapRecursiveTransformer;

class ArrayMapRecursiveTransformerTest extends TestBase
{
    /**
     * @dataProvider arrayMapRecursiveProvider
     *
     * @param callable $callable
     * @param array    $data
     * @param array    $expected
     *
     * @throws NodalFlowException
     */
    public function testArrayMapRecursiveTransformer(callable $callable, array $data, array $expected)
    {
        $transformer = new ArrayMapRecursiveTransformer($callable);

        $this->assertSame($expected, $transformer->exec($data));
    }

    public function arrayMapRecursiveProvider(): array
    {
        return [
            [
                'strtolower',
                [
                    'UPPER',
                    'case'  => 'Upper',
                    'array' => [
                        'UPPER',
                        'case' => 'Upper',
                    ],
                ],
                [
                    'upper',
                    'case'  => 'upper',
                    'array' => [
                        'upper',
                        'case' => 'upper',
                    ],
                ],
            ],
            [
                function ($value) {
                    return trim($value);
                },
                [
                    '   un     trimmed    ',
                    'case'  => 'trimmed',
                    'array' => [
                        '   un     trimmed    ',
                        'case' => 'trimmed',
                    ],
                ],
                [
                    'un     trimmed',
                    'case'  => 'trimmed',
                    'array' => [
                        'un     trimmed',
                        'case' => 'trimmed',
                    ],
                ],
            ],
        ];
    }
}
