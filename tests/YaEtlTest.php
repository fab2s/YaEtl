<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\YaEtl\Extractors\OnClause;
use fab2s\YaEtl\Extractors\PdoExtractor;
use fab2s\YaEtl\Extractors\PdoUniqueKeyExtractor;
use fab2s\YaEtl\Transformers\CallableTransformer;
use fab2s\YaEtl\Transformers\NoOpTransformer;
use fab2s\YaEtl\YaEtl;

class YaEtlTest extends \TestCase
{
    /**
     * @dataProvider fromCasesProvider
     *
     * @param YaEtl $flow
     * @param array $expectations
     * @param mixed $mocks
     */
    public function testFrom(YaEtl $flow, $expectations, $mocks)
    {
        $expectedTable = $this->getConnection()->createQueryTable($expectations['expectedTable']['table'], $expectations['expectedTable']['query']);
        $resultTable   = $this->getConnection()->createQueryTable($expectations['resultTable']['table'], $expectations['resultTable']['query']);

        $this->assertSame(0, $this->getConnection()->getRowCount(self::TO_TABLE), 'To table not initialized');
        // exec flow
        $flow->exec();

        $this->assertSame($expectations['resultCount'], $this->getConnection()->getRowCount(self::TO_TABLE), 'To table not properly updated');

        $this->assertTablesEqual($expectedTable, $resultTable, self::TO_TABLE . ' did not match ' . self::FROM_TABLE);

        if (!empty($mocks['loader'])) {
            $spyInvocations = $mocks['loader']['spy']->getInvocations();
            $this->assertSame(count($spyInvocations), $this->numRecords);

            $idx      = 0;
            $result   = [];
            $pdoQuery = $this->getPdo()->prepare('SELECT * FROM ' . self::TO_TABLE);
            $pdoQuery->execute();
            while ($row = $pdoQuery->fetch(\PDO::FETCH_ASSOC)) {
                $result[] = $row;
                $this->assertSame($spyInvocations[$idx]->parameters[0], $row, 'argument / result missmatch');
                ++$idx;
            }
        }
    }

    /**
     * @dataProvider joinCasesProvider
     *
     * @param YaEtl $flow
     * @param array $expectations
     * @param mixed $mocks
     */
    public function testJoin(YaEtl $flow, $expectations, $mocks)
    {
        $expectedTable = $this->getConnection()->createQueryTable($expectations['expectedTable']['table'], $expectations['expectedTable']['query']);
        $resultTable   = $this->getConnection()->createQueryTable($expectations['resultTable']['table'], $expectations['resultTable']['query']);

        $this->assertSame(0, $this->getConnection()->getRowCount(self::TO_TABLE), 'To table not initialized');

        // exec flow
        $flow->exec();

        $this->assertSame($expectations['resultCount'], $this->getConnection()->getRowCount(self::TO_TABLE), 'To table not properly updated, got:' . $this->getConnection()->getRowCount(self::TO_TABLE) . ' expected:' . $expectations['resultCount']);

        $this->assertTablesEqual($expectedTable, $resultTable, self::TO_TABLE . ' did not match ' . self::FROM_TABLE);

        if (!empty($mocks['loader'])) {
            $spyInvocations = $mocks['loader']['spy']->getInvocations();
            $this->assertSame(count($spyInvocations), $expectations['resultCount']);

            $idx      = 0;
            $result   = [];
            $pdoQuery = $this->getPdo()->prepare('SELECT * FROM ' . self::TO_TABLE);
            $pdoQuery->execute();
            while ($row = $pdoQuery->fetch(\PDO::FETCH_ASSOC)) {
                $result[] = $row;
                $this->assertSame($spyInvocations[$idx]->parameters[0], $row, 'argument / result missmatch');
                ++$idx;
            }
        }
    }

    /**
     * @return array
     */
    public function joinCasesProvider()
    {
        $fromQuery = 'SELECT * FROM ' . self::FROM_TABLE . ' ORDER BY id ASC';
        $toQuery   = 'SELECT * FROM ' . self::TO_TABLE . ' ORDER BY id ASC';
        $joinQuery = 'SELECT * FROM ' . self::JOIN_TABLE;
        $fullFrom1 = new PdoUniqueKeyExtractor($this->getPdo(), $fromQuery, 'id');
        $fullFrom1->setBatchSize(42);
        $fullFrom2 = new PdoUniqueKeyExtractor($this->getPdo(), $fromQuery, 'id');
        $fullFrom2->setBatchSize(1337);
        $fullFrom3 = new PdoUniqueKeyExtractor($this->getPdo(), $fromQuery, 'id');
        $fullFrom3->setBatchSize(77);
        $fullFrom4 = new PdoUniqueKeyExtractor($this->getPdo(), $fromQuery, 'id');
        $fullFrom4->setBatchSize(10);

        $joiner1 = new PdoUniqueKeyExtractor($this->getPdo(), $joinQuery, 'id');
        $joiner1->setBatchSize(10);
        $joiner2 = new PdoUniqueKeyExtractor($this->getPdo(), $joinQuery, 'id');
        $joiner2->setBatchSize(1337);

        $joinOnClause  = new OnClause('id', 'id', function ($upstreamRecord, $record) {
            return array_replace($upstreamRecord, $record);
        });

        $leftJoinOnClause  = new OnClause('id', 'id', function ($upstreamRecord, $record) {
            return array_replace($upstreamRecord, $record);
        }, [
            'join_id' => null,
        ]);

        $loaderMockSetup1 = $this->getLoaderMock();
        $loaderMockSetup2 = $this->getLoaderMock();
        $loaderMockSetup3 = $this->getLoaderMock();
        $loaderMockSetup4 = $this->getLoaderMock();

        return [
            [
                // test a join : success means that the to table ends up
                // exactly like join table, that is, every join_id are set
                // and missmatch are skipped
                'flow'          => (new YaEtl)
                    ->from($fullFrom1)
                    ->join(new PdoUniqueKeyExtractor($this->getPdo(), $joinQuery, 'id'), $fullFrom1, $joinOnClause)
                    ->to($loaderMockSetup1['mock']),
                'expectations'  => [
                    // here we assert that the content TO_TABLE
                    // is identical to JOIN_TABLE as we are in
                    // regular join mode
                    'resultTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $toQuery,
                    ],
                    'expectedTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $joinQuery,
                    ],
                    'resultCount' => $this->numRecords / 2,
                ],
                'mocks'         => [
                    'loader' => $loaderMockSetup1,
                ],
            ],
            [
                // test a left join : success means that the to table ends up
                // exactly like LEFT_JOIN_RESULT_TABLE where one record out of
                // two holds a null join_id
                'flow'          => (new YaEtl)
                    ->from($fullFrom2)
                    ->join(new PdoUniqueKeyExtractor($this->getPdo(), $joinQuery, 'id'), $fullFrom2, $leftJoinOnClause)
                    ->to($loaderMockSetup2['mock']),
                'expectations'  => [
                    // here we assert that the content TO_TABLE
                    // is identical to JOIN_TABLE as we are in
                    // regular join mode
                    'resultTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $toQuery,
                    ],
                    'expectedTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => 'SELECT * FROM ' . self::LEFT_JOIN_RESULT_TABLE . ' ORDER BY id ASC',
                    ],
                    'resultCount' => $this->numRecords,
                ],
                'mocks'         => [
                    'loader' => $loaderMockSetup2,
                ],
            ],
            [
                // test left joined join = join
                'flow'          => (new YaEtl)
                    ->from($fullFrom3)
                    ->join($joiner1, $fullFrom3, $joinOnClause)
                    ->join(new PdoUniqueKeyExtractor($this->getPdo(), $joinQuery, 'id'), $joiner1, $leftJoinOnClause)
                    ->to($loaderMockSetup3['mock']),
                'expectations'  => [
                    // here we assert that the content TO_TABLE
                    // is identical to JOIN_TABLE as we are in
                    // regular join mode
                    'resultTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $toQuery,
                    ],
                    'expectedTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => 'SELECT * FROM ' . self::JOIN_RESULT_TABLE . ' ORDER BY id ASC',
                    ],
                    'resultCount' => $this->numRecords / 2,
                ],
                'mocks'         => [
                    'loader' => $loaderMockSetup3,
                ],
            ],
            [
                // same as left join test with unbalanced batchsizes
                'flow'          => (new YaEtl)
                    ->from($fullFrom4)
                    ->join($joiner2, $fullFrom4, $leftJoinOnClause)
                    ->to($loaderMockSetup4['mock']),
                'expectations'  => [
                    // here we assert that the content TO_TABLE
                    // is identical to JOIN_TABLE as we are in
                    // regular join mode
                    'resultTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $toQuery,
                    ],
                    'expectedTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => 'SELECT * FROM ' . self::LEFT_JOIN_RESULT_TABLE . ' ORDER BY id ASC',
                    ],
                    'resultCount' => $this->numRecords,
                ],
                'mocks'         => [
                    'loader' => $loaderMockSetup4,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function fromCasesProvider()
    {
        $fromQuery = 'SELECT * FROM ' . self::FROM_TABLE . ' ORDER BY id ASC';
        $toQuery   = 'SELECT * FROM ' . self::TO_TABLE . ' ORDER BY id ASC';
        $fullFrom  = new PdoExtractor($this->getPdo(), $fromQuery);

        $FirstHalfFrom = clone $fullFrom;
        $FirstHalfFrom->setLimit(floor($this->numRecords / 2))
                ->setBatchSize(10);

        $SecondHalfFrom = clone $FirstHalfFrom;
        $SecondHalfFrom->setLimit(ceil($this->numRecords / 2))
                ->setOffset(floor($this->numRecords / 2))
                ->setBatchSize(20);

        $FirstTenFrom = clone $fullFrom;
        $FirstTenFrom->setLimit(10);
        $AfterTenFrom = clone $fullFrom;
        $AfterTenFrom->setOffset(10);
        $AfterTenFrom->setLimit(10);
        $AfterTenFrom->setBatchSize(10);

        $loaderMockSetup1 = $this->getLoaderMock();
        $loaderMockSetup2 = $this->getLoaderMock();
        $loaderMockSetup3 = $this->getLoaderMock();
        $loaderMockSetup4 = $this->getLoaderMock();
        $loaderMockSetup5 = $this->getLoaderMock();
        $loaderMockSetup6 = $this->getLoaderMock();

        return [
            [
                'flow'          => (new YaEtl)
                    ->from(new PdoExtractor($this->getPdo(), $fromQuery))
                    ->to($loaderMockSetup1['mock']),
                'expectations' => [
                    'resultTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $toQuery,
                    ],
                    'expectedTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $fromQuery,
                    ],
                    'resultCount' => $this->numRecords,
                ],
                'mocks'         => [
                    'loader' => $loaderMockSetup1,
                ],
            ],
            [
                'flow'          => (new YaEtl)
                    ->from(new PdoExtractor($this->getPdo(), $fromQuery))
                    ->transform(new NoOpTransformer)
                    ->transform(new CallableTransformer(function ($record) {
                        return $record;
                    }))
                    ->to($loaderMockSetup2['mock']),
                'expectations'  => [
                    'resultTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $toQuery,
                    ],
                    'expectedTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $fromQuery,
                    ],
                    'resultCount' => $this->numRecords,
                ],
                'mocks'         => [
                    'loader' => $loaderMockSetup2,
                ],
            ],
            [
                'flow'          => (new YaEtl)
                    ->from($FirstHalfFrom)
                    ->from($SecondHalfFrom, $FirstHalfFrom)
                    ->transform(new NoOpTransformer)
                    ->to($loaderMockSetup3['mock']),
                'expectations'  => [
                    'resultTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $toQuery,
                    ],
                    'expectedTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $fromQuery,
                    ],
                    'resultCount' => $this->numRecords,
                ],
                'mocks'         => [
                    'loader' => $loaderMockSetup3,
                ],
            ],
            [
                'flow'          => (new YaEtl)
                    ->from($FirstTenFrom)
                    ->from($AfterTenFrom)
                    ->transform(new NoOpTransformer)
                    ->to($loaderMockSetup4['mock']),
                'expectations'  => [
                    'resultTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $toQuery,
                    ],
                    'expectedTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => 'SELECT * FROM ' . self::FROM_TABLE . ' WHERE id > 10 ORDER BY id ASC',
                    ],
                    'resultCount' => $this->numRecords - 10,
                ],
                'mocks'         => [],
            ],
            [
                'flow'          => (new YaEtl)
                    ->from(new PdoUniqueKeyExtractor($this->getPdo(), $fromQuery, 'id'))
                    ->to($loaderMockSetup5['mock']),
                'expectations'  => [
                    'resultTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $toQuery,
                    ],
                    'expectedTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $fromQuery,
                    ],
                    'resultCount' => $this->numRecords,
                ],
                'mocks'         => [
                    'loader' => $loaderMockSetup5,
                ],
            ],
            [
                'flow'          => (new YaEtl)
                    ->from(new PdoExtractor($this->getPdo(), $fromQuery))
                    ->transform(new NoOpTransformer)
                    ->to($loaderMockSetup6['mock'])
                    ->transform(new NoOpTransformer),
                'expectations'  => [
                    'resultTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $toQuery,
                    ],
                    'expectedTable' => [
                        'table' => self::FROM_TABLE,
                        'query' => $fromQuery,
                    ],
                    'resultCount' => $this->numRecords,
                ],
                'mocks'         => [
                    'loader' => $loaderMockSetup6,
                ],
            ],
        ];
    }
}
