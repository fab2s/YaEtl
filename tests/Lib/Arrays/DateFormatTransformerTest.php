<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib\Arrays;

use DateTimeImmutable;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\Tests\Lib\TestBase;
use fab2s\YaEtl\Transformers\Arrays\DateFormatTransformer;

class DateFormatTransformerTest extends TestBase
{
    protected static $datetime;

    /**
     * @dataProvider dateFormatProvider
     *
     * @param array $setup
     * @param array $data
     * @param array $expected
     *
     * @throws NodalFlowException
     */
    public function testDateFormatTransformer(array $setup, array $data, array $expected)
    {
        $transformer = new DateFormatTransformer($setup);

        $this->assertEquals($expected, $transformer->exec($data));
    }

    public function dateFormatProvider(): array
    {
        return [
            [
                [
                    'key1' => 'Y-m-d H:i:s',
                    'key2' => ['from' => 'Y-m-d H:i:s'],
                    'key3' => ['from' => 'Y-m-d H:i:s', 'to' => 'd/m/Y'],
                ],
                [
                    'key1' => '2019-06-14 14:14:14',
                    'key2' => '2019-06-14 14:14:14',
                    'key3' => '2019-06-14 14:14:14',
                ],
                [
                    'key1' => DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-06-14 14:14:14'),
                    'key2' => '2019-06-14 14:14:14',
                    'key3' => '14/06/2019',
                ],
            ],
        ];
    }
}
