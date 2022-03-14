<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\YaEtlException;
use fab2s\YaEtl\Extractors\OnClause;
use fab2s\YaEtl\Extractors\PdoUniqueKeyExtractor;
use fab2s\YaEtl\YaEtl;

/**
 * Class JoinTest
 */
class JoinTest extends \TestBase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->populateTable(self::FROM_TABLE)
            ->populateTable(self::JOIN_TABLE);
    }

    /**
     * @dataProvider joinCasesProvider
     *
     * @param YaEtl $flow
     * @param bool  $isLeft
     *
     * @throws NodalFlowException
     */
    public function testJoin(YaEtl $flow, bool $isLeft)
    {
        $this->resetResultTable();
        $this->assertSame($this->numRecords, $this->getTableCount(self::FROM_TABLE), 'From table not initialized');
        $this->assertSame(0, $this->getTableCount(self::TO_TABLE), 'To table not initialized');

        // exec flow
        $flow->exec();

        $expectedNumRecords = $isLeft ? $this->numRecords : $this->numRecords / 2;
        $this->assertSame($expectedNumRecords, $this->getTableCount(self::TO_TABLE), 'To table not properly updated, got:' . $this->getTableCount(self::TO_TABLE) . ' expected:' . $expectedNumRecords . ' in ' . ($isLeft ? 'LeftJoin' : 'Join') . ' mode');

        $fromRecords     = $this->getTableAll(self::FROM_TABLE);
        $expectedRecords = [];

        if ($isLeft) {
            foreach ($fromRecords as $idx => $record) {
                $id                = $record['id'];
                $expectedRecords[] = !isset($this->expectedJoinRecords[$id]) ? $record : $this->expectedJoinRecords[$id];
            }
        } else {
            $expectedRecords = array_values($this->expectedJoinRecords);
        }

        $this->assertEquals($expectedRecords, $this->getTableAll(self::TO_TABLE), 'Result table did not match ' . ($isLeft ? 'Left' : '') . 'Join constraint');
    }

    /**
     * @throws NodalFlowException
     * @throws YaEtlException
     *
     * @return array
     */
    public function joinCasesProvider()
    {
        $fromQuery = 'SELECT * FROM ' . self::FROM_TABLE . ' ORDER BY id ASC';
        $joinQuery = 'SELECT * FROM ' . self::JOIN_TABLE;
        $fullFrom1 = new PdoUniqueKeyExtractor($this->getPdo(), $fromQuery, 'id');
        $fullFrom1->setBatchSize(42);
        $fullFrom2 = clone $fullFrom1;
        $fullFrom2->setBatchSize(1337);
        $fullFrom3 = clone $fullFrom1;
        $fullFrom3->setBatchSize(77);
        $fullFrom4 = clone $fullFrom1;
        $fullFrom4->setBatchSize(10);

        $joiner1 = new PdoUniqueKeyExtractor($this->getPdo(), $joinQuery, 'id');
        $joiner1->setBatchSize(10);
        $joiner2 = clone $joiner1;
        $joiner2->setBatchSize(1337);

        $joinOnClause  = new OnClause('id', 'id', function ($upstreamRecord, $record) {
            return array_replace($upstreamRecord, $record);
        });

        $leftJoinOnClause  = new OnClause('id', 'id', function ($upstreamRecord, $record) {
            return array_replace($upstreamRecord, $record);
        }, [
            'join_id' => null,
        ]);

        return [
            [
                // test a join : success means that the to table ends up
                // exactly like join table, that is, every join_id are set
                // and mismatch are skipped
                'flow'          => (new YaEtl)
                    ->from($fullFrom1)
                    ->join(new PdoUniqueKeyExtractor($this->getPdo(), $joinQuery, 'id'), $fullFrom1, $joinOnClause)
                    ->to($this->getLoaderMock()),
                'isLeft' => false,
            ],
            [
                // test a left join : success means that the to table ends up
                // exactly like LEFT_JOIN_RESULT_TABLE where one record out of
                // two holds a null join_id
                'flow'          => (new YaEtl)
                    ->from($fullFrom2)
                    ->join(new PdoUniqueKeyExtractor($this->getPdo(), $joinQuery, 'id'), $fullFrom2, $leftJoinOnClause)
                    ->to($this->getLoaderMock()),
                'isLeft' => true,
            ],
            [
                // test left joined join = join
                'flow'          => (new YaEtl)
                    ->from($fullFrom3)
                    ->join($joiner1, $fullFrom3, $joinOnClause)
                    ->join(new PdoUniqueKeyExtractor($this->getPdo(), $joinQuery, 'id'), $joiner1, $leftJoinOnClause)
                    ->to($this->getLoaderMock()),
                'isLeft' => false,
            ],
            [
                // same as left join test with unbalanced batchSizes
                'flow'          => (new YaEtl)
                    ->from($fullFrom4)
                    ->join($joiner2, $fullFrom4, $leftJoinOnClause)
                    ->to($this->getLoaderMock()),
                'isLeft' => true,
            ],
        ];
    }
}
