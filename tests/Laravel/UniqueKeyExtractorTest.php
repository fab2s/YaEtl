<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Laravel;

use fab2s\YaEtl\Extractors\ExtractorAbstract;
use fab2s\YaEtl\Extractors\OnClause;
use fab2s\YaEtl\Laravel\Extractors\UniqueKeyExtractor;
use fab2s\YaEtl\Loaders\ArrayLoader;
use fab2s\YaEtl\YaEtl;
use fab2s\YaEtl\YaEtlException;
use PHPUnit\Framework\Attributes\DataProvider;

class UniqueKeyExtractorTest extends LaravelTestCase
{
    use ExtractionTestTrait;

    public function test_db_extractor_exception()
    {
        $this->expectException(YaEtlException::class);
        (new UniqueKeyExtractor)->setExtractQuery(null);
    }

    public function test_db_extractor_exception_type()
    {
        $this->expectException(YaEtlException::class);
        (new UniqueKeyExtractor)->setExtractQuery(TestModel::query());
    }

    /**
     * @throws YaEtlException
     * @throws \fab2s\NodalFlow\NodalFlowException
     */
    #[DataProvider('trueFalseProvider')]
    public function test_join(bool $every)
    {
        $this->createTestJoinModelTable()
            ->seedTestJoinModelTable($every);
        $joinOnClause = new OnClause('id', 'model_id', function ($upstreamRecord, $record) {
            unset($record['id']);

            return array_replace($upstreamRecord, $record);
        });

        $arrayLoader   = new ArrayLoader;
        $fromExtractor = new UniqueKeyExtractor($this->getTestQuery(), 'id');
        $joinExtractor = new UniqueKeyExtractor(TestJoinModel::getQuery(), 'id');
        $yaEtl         = new YaEtl;
        $yaEtl
            ->from($fromExtractor)
            ->join($joinExtractor, $fromExtractor, $joinOnClause)
            ->to($arrayLoader)
            ->exec();

        $this->assertEquals($this->getExpectedJoinedData($every), $arrayLoader->getLoadedData());
    }

    /**
     * @throws YaEtlException
     * @throws \fab2s\NodalFlow\NodalFlowException
     */
    #[DataProvider('trueFalseProvider')]
    public function test_left_join(bool $every)
    {
        $this->createTestJoinModelTable()
            ->seedTestJoinModelTable($every);

        $leftJoinOnClause = new OnClause('id', 'model_id', function ($upstreamRecord, $record) {
            unset($record['id']);

            return array_replace($upstreamRecord, $record);
        }, [
            'model_id' => null,
            'join'     => null,
        ]);

        $arrayLoader   = new ArrayLoader;
        $fromExtractor = new UniqueKeyExtractor($this->getTestQuery(), 'id');
        $joinExtractor = new UniqueKeyExtractor(TestJoinModel::getQuery(), 'id');
        (new YaEtl)
            ->from($fromExtractor)
            ->join($joinExtractor, $fromExtractor, $leftJoinOnClause)
            ->to($arrayLoader)
            ->exec();

        $this->assertEquals($this->getExpectedJoinedData($every, true), $arrayLoader->getLoadedData());
    }

    public static function trueFalseProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    protected function getExpectedJoinedData(bool $every = true, bool $isLeft = false): array
    {
        $result             = [];
        $expectedJoinedData = [];
        foreach ($this->getExpectedTestJoinModelData($every) as $record) {
            $expectedJoinedData[$record['model_id']] = $record;
        }

        foreach ($this->getExpectedTestModelData() as $expectedTestModelData) {
            $modelId = $expectedTestModelData['id'];
            if (!isset($expectedJoinedData[$modelId])) {
                if ($isLeft) {
                    $result[] = array_replace($expectedTestModelData, [
                        'model_id' => null,
                        'join'     => null,
                    ]);
                }

                continue;
            }
            $joinedModel = $expectedJoinedData[$modelId];

            $expectedTestModelData['model_id'] = $modelId;
            $expectedTestModelData['join']     = $joinedModel['join'];
            $result[]                          = $expectedTestModelData;
        }

        return $result;
    }

    protected function getTestQuery()
    {
        return TestModel::getQuery()->orderBy('id', 'asc');
    }

    protected function getExtractor(): ExtractorAbstract
    {
        return new UniqueKeyExtractor($this->getTestQuery());
    }
}
