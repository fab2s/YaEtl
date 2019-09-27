<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Transformers\Arrays\ArrayReplaceRecursiveTransformer;

class ArrayReplaceRecursiveTransformerTest extends \TestBase
{
    public function arrayReplaceRecursiveProvider()
    {
        return [
          [
              'default' => [
                  'one' => 'onedefault',
                  42    => [1, 3, 3, 7],
              ],
              'override' => [
                  '?' => ['no', 'maybe'],
              ],
              'cases' => [
                  [
                      'input' => [
                          '?'  => 'yes',
                          42   => [7, 3, 3, 1],
                      ],
                      'expected' => [
                          'one' => 'onedefault',
                          42    => [7, 3, 3, 1],
                          '?'   => ['no', 'maybe'],
                      ],
                  ],
                  [
                      'input' => [
                          '?'  => ['a', 'b', 'never'],
                          42   => null,
                      ],
                      'expected' => [
                          'one' => 'onedefault',
                          42    => null,
                          '?'   => ['no', 'maybe', 'never'],
                      ],
                  ],
              ],
          ],
        ];
    }

    /**
     * @dataProvider arrayReplaceRecursiveProvider
     *
     * @param array $default
     * @param array $override
     * @param array $cases
     *
     * @throws NodalFlowException
     */
    public function testArrayReplaceRecursiveTransformer(array $default, array $override, array $cases)
    {
        $transformer = new ArrayReplaceRecursiveTransformer($default, $override);

        foreach ($cases as $case) {
            $this->assertSame($case['expected'], $transformer->exec($case['input']));
        }
    }
}
