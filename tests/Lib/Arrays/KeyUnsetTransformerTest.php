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
use fab2s\YaEtl\Transformers\Arrays\KeyUnsetTransformer;

class KeyUnsetTransformerTest extends TestBase
{
    /**
     * @dataProvider keyUnsetProvider
     *
     * @param array $unsetList
     * @param array $data
     * @param array $expected
     *
     * @throws NodalFlowException
     */
    public function testKeyUnsetTransformer(array $unsetList, array $data, array $expected)
    {
        $transformer = new KeyUnsetTransformer($unsetList);

        $this->assertSame($expected, $transformer->exec($data));
    }

    public function keyUnsetProvider(): array
    {
        return [
            [
                [
                    'key2',
                ],
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'key3' => 'val3',
                ],
                [
                    'key1' => 'val1',
                    'key3' => 'val3',
                ],
            ],
            [
                [
                    'key2', 'key1', 'key1', 'notHere',
                ],
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'key3' => 'val3',
                ],
                [
                    'key3' => 'val3',
                ],
            ],
        ];
    }
}
