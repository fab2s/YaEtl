<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Transformers\Arrays\ArrayReplaceTransformer;

class ArrayReplaceTransformerTest extends \TestBase
{
    public function arrayReplaceProvider()
    {
        return [
          [
              'default' => [
                  'one' => 'onedefault',
                  42    => 1337,
              ],
              'override' => [
                  '?' => 'no',
              ],
              'cases' => [
                  [
                      'input' => [
                          '?'  => 'yes',
                          'oh' => 'my',
                      ],
                      'expected' => [
                          'one' => 'onedefault',
                          42    => 1337,
                          '?'   => 'no',
                          'oh'  => 'my',
                      ],
                  ],
                  [
                      'input' => [
                          '?'  => ['a', 'b', 'c'],
                          42   => null,
                      ],
                      'expected' => [
                          'one' => 'onedefault',
                          42    => null,
                          '?'   => 'no',
                      ],
                  ],
              ],
          ],
        ];
    }

    /**
     * @dataProvider arrayReplaceProvider
     *
     * @param array $default
     * @param array $override
     * @param array $cases
     *
     * @throws NodalFlowException
     */
    public function testArrayReplaceTransformer(array $default, array $override, array $cases)
    {
        $transformer = new ArrayReplaceTransformer($default, $override);

        foreach ($cases as $case) {
            $this->assertSame($case['expected'], $transformer->exec($case['input']));
        }
    }
}
