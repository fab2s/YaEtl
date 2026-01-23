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
use PHPUnit\Framework\Attributes\DataProvider;

class ArrayMapTransformerTest extends TestBase
{
    /**
     * @throws NodalFlowException
     */
    #[DataProvider('arrayMapProvider')]
    public function test_array_map_transformer(callable $callable, array $data)
    {
        $transformer = new ArrayMapTransformer($callable);

        $this->assertSame(array_map($callable, $data), $transformer->exec($data));
    }

    public static function arrayMapProvider(): array
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
