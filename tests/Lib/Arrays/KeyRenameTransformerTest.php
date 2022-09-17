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
use fab2s\YaEtl\Transformers\Arrays\KeyRenameTransformer;

class KeyRenameTransformerTest extends TestBase
{
    /**
     * @dataProvider keyRenameProvider
     *
     * @param array $aliases
     * @param array $data
     * @param array $expected
     *
     * @throws NodalFlowException
     */
    public function testKeyRenameTransformer(array $aliases, array $data, array $expected)
    {
        $transformer = new KeyRenameTransformer($aliases);

        $this->assertSame($expected, $transformer->exec($data));
    }

    public function keyRenameProvider(): array
    {
        return [
            [
                [
                    'old' => 'new',
                ],
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'old'  => 'oho',
                ],
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'new'  => 'oho',
                ],
            ],
            [
                [
                    'old'  => 'new',
                    'key2' => 'new2',
                ],
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'old'  => 'oho',
                ],
                [
                    'key1' => 'val1',
                    'new'  => 'oho',
                    'new2' => 'val2',
                ],
            ],
            [
                [
                    'old'  => 'new',
                    'key2' => 'key1',
                ],
                [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'old'  => 'oho',
                ],
                [
                    'key1' => 'val2',
                    'new'  => 'oho',
                ],
            ],
        ];
    }
}
