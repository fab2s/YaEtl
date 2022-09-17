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
use fab2s\YaEtl\Transformers\Arrays\ArrayMapTransformer;

class ArrayMapTransformerTest extends TestBase
{
    /**
     * @dataProvider arrayMapProvider
     *
     * @param callable $callable
     * @param array    $data
     *
     * @throws NodalFlowException
     */
    public function testArrayMapTransformer(callable $callable, array $data)
    {
        $transformer = new ArrayMapTransformer($callable);

        $this->assertSame(array_map($callable, $data), $transformer->exec($data));
    }

    public function arrayMapProvider(): array
    {
        return [
            [
                'strtolower',
                [
                    'UPPER',
                    'case' => 'Upper',
                ],
            ],
            [
                function ($value) {
                    return trim($value);
                },
                [
                    '   un     trimmed    ',
                    'case' => 'trimmed',
                ],
            ],
        ];
    }
}
