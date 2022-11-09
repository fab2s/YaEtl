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
use fab2s\YaEtl\Transformers\Arrays\SetValuesTransformer;

class SetValuesTransformerTest extends TestBase
{
    /**
     * @dataProvider setValuesProvider
     *
     * @param array $setup
     * @param array $data
     * @param array $expected
     *
     * @throws NodalFlowException
     */
    public function testSetValuesTransformer(array $setup, array $data, array $expected)
    {
        $transformer = new SetValuesTransformer($setup);

        $this->assertSame($expected, $transformer->exec($data));
    }

    public function setValuesProvider(): array
    {
        return [
            [
                [
                    'key4' => 'Val4',
                    'key5' => 5,
                    'key6' => [],
                ],
                [
                    'key1' => 'Val1',
                    'key2' => 'Val2',
                    'key3' => 'Val3',
                ],
                [
                    'key1' => 'Val1',
                    'key2' => 'Val2',
                    'key3' => 'Val3',
                    'key4' => 'Val4',
                    'key5' => 5,
                    'key6' => [],
                ],
            ],
        ];
    }
}
