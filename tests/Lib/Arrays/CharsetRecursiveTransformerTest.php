<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib\Arrays;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\Strings\Strings;
use fab2s\Tests\Lib\TestBase;
use fab2s\YaEtl\Transformers\Arrays\CharsetRecursiveTransformer;

class CharsetRecursiveTransformerTest extends TestBase
{
    /**
     * @dataProvider charsetRecursiveProvider
     *
     * @param string $from
     * @param string $to
     * @param array  $data
     * @param array  $expected
     *
     * @throws NodalFlowException
     */
    public function testCharsetRecursiveTransformer(string $from, string $to, array $data, array $expected)
    {
        $transformer = new CharsetRecursiveTransformer($from, $to);

        $this->assertSame($expected, $transformer->exec($data));
    }

    public function charsetRecursiveProvider(): array
    {
        return [
            [
                'ISO-8859-1',
                'UTF-8',
                [
                    'key1'   => Strings::convert('iñtërnâtiônàlizætiøn', 'UTF-8', 'ISO-8859-1'),
                    'key2'   => 1,
                    'key3'   => [],
                    'array'  => [
                        'key1'   => Strings::convert('iñtërnâtiônàlizætiøn', 'UTF-8', 'ISO-8859-1'),
                        'key2'   => 1,
                        'key3'   => [],
                    ],
                ],
                [
                    'key1'   => 'iñtërnâtiônàlizætiøn',
                    'key2'   => 1,
                    'key3'   => [],
                    'array'  => [
                        'key1' => 'iñtërnâtiônàlizætiøn',
                        'key2' => 1,
                        'key3' => [],
                    ],
                ],
            ],
        ];
    }
}
