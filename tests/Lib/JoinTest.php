<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Extractors\OnClause;
use fab2s\YaEtl\Extractors\PdoUniqueKeyExtractor;
use fab2s\YaEtl\YaEtl;
use fab2s\YaEtl\YaEtlException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class JoinTest
 */
class JoinTest extends TestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->populateTable(self::FROM_TABLE)
            ->populateTable(self::JOIN_TABLE)
        ;
    }

    /**
     * @throws NodalFlowException
     */
    #[DataProvider('joinCasesProvider')]
    public function test_join(YaEtl $flow, bool $isLeft)
    {
        $this->resetResultTable();
        $this->assertSame(self::$numRecords, $this->getTableCount(self::FROM_TABLE), 'From table not initialized');
        $this->assertSame(0, $this->getTableCount(self::TO_TABLE), 'To table not initialized');

        // exec flow
        $flow->exec();

        $expectedNumRecords = $isLeft ? self::$numRecords : self::$numRecords / 2;
        $this->assertSame($expectedNumRecords, $this->getTableCount(self::TO_TABLE), 'To table not properly updated, got:' . $this->getTableCount(self::TO_TABLE) . ' expected:' . $expectedNumRecords . ' in ' . ($isLeft ? 'LeftJoin' : 'Join') . ' mode');

        $fromRecords     = $this->getTableAll(self::FROM_TABLE);
        $expectedRecords = [];

        if ($isLeft) {
            foreach ($fromRecords as $idx => $record) {
                $id                = $record['id'];
                $expectedRecords[] = ! isset(self::$expectedJoinRecords[$id]) ? $record : self::$expectedJoinRecords[$id];
            }
        } else {
            $expectedRecords = array_values(self::$expectedJoinRecords);
        }

        $this->assertEquals($expectedRecords, $this->getTableAll(self::TO_TABLE), 'Result table did not match ' . ($isLeft ? 'Left' : '') . 'Join constraint');
    }

    /**
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public static function joinCasesProvider(): array
    {
        $fromQuery = 'SELECT * FROM ' . self::FROM_TABLE . ' ORDER BY id ASC';
        $joinQuery = 'SELECT * FROM ' . self::JOIN_TABLE;
        $fullFrom1 = new PdoUniqueKeyExtractor(self::getPdo(), $fromQuery, 'id');
        $fullFrom1->setBatchSize(42);
        $fullFrom2 = clone $fullFrom1;
        $fullFrom2->setBatchSize(1337);
        $fullFrom3 = clone $fullFrom1;
        $fullFrom3->setBatchSize(77);
        $fullFrom4 = clone $fullFrom1;
        $fullFrom4->setBatchSize(10);

        $joiner1 = new PdoUniqueKeyExtractor(self::getPdo(), $joinQuery, 'id');
        $joiner1->setBatchSize(10);
        $joiner2 = clone $joiner1;
        $joiner2->setBatchSize(1337);

        $joinOnClause = new OnClause('id', 'id', function ($upstreamRecord, $record) {
            return array_replace($upstreamRecord, $record);
        });

        $leftJoinOnClause = new OnClause('id', 'id', function ($upstreamRecord, $record) {
            return array_replace($upstreamRecord, $record);
        }, [
            'join_id' => null,
        ]);

        return [
            [
                // test a join : success means that the to table ends up
                // exactly like join table, that is, every join_id are set
                // and mismatch are skipped
                (new YaEtl)
                    ->from($fullFrom1)
                    ->join(new PdoUniqueKeyExtractor(self::getPdo(), $joinQuery, 'id'), $fullFrom1, $joinOnClause)
                    ->to(new InsertLoader),
                false,
            ],
            [
                // test a left join : success means that the to table ends up
                // exactly like LEFT_JOIN_RESULT_TABLE where one record out of
                // two holds a null join_id
                (new YaEtl)
                    ->from($fullFrom2)
                    ->join(new PdoUniqueKeyExtractor(self::getPdo(), $joinQuery, 'id'), $fullFrom2, $leftJoinOnClause)
                    ->to(new InsertLoader),
                true,
            ],
            [
                // test left joined join = join
                (new YaEtl)
                    ->from($fullFrom3)
                    ->join($joiner1, $fullFrom3, $joinOnClause)
                    ->join(new PdoUniqueKeyExtractor(self::getPdo(), $joinQuery, 'id'), $joiner1, $leftJoinOnClause)
                    ->to(new InsertLoader),
                false,
            ],
            [
                // same as left join test with unbalanced batchSizes
                (new YaEtl)
                    ->from($fullFrom4)
                    ->join($joiner2, $fullFrom4, $leftJoinOnClause)
                    ->to(new InsertLoader),
                true,
            ],
        ];
    }
}
