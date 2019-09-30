<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\YaEtlException;
use fab2s\YaEtl\Extractors\PdoExtractor;
use fab2s\YaEtl\Extractors\PdoUniqueKeyExtractor;
use fab2s\YaEtl\Transformers\CallableTransformer;
use fab2s\YaEtl\Transformers\NoOpTransformer;
use fab2s\YaEtl\YaEtl;

/**
 * Class FromTest
 */
class FromTest extends \TestBase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->populateTable(self::FROM_TABLE);
    }

    /**
     * @dataProvider fromCasesProvider
     *
     * @param YaEtl $flow
     *
     * @throws NodalFlowException
     */
    public function testFrom(YaEtl $flow)
    {
        $this->resetResultTable();
        $this->assertSame($this->numRecords, $this->getTableCount(self::FROM_TABLE), 'From table not initialized');
        $this->assertSame(0, $this->getTableCount(self::TO_TABLE), 'To table not initialized');

        // exec flow
        $flow->exec();

        $this->assertSame($this->numRecords, $this->getTableCount(self::TO_TABLE), 'To table not properly updated');

        $this->assertSame($this->getTableAll(self::FROM_TABLE), $this->getTableAll(self::TO_TABLE), self::TO_TABLE . ' did not match ' . self::FROM_TABLE);
    }

    /**
     * @throws YaEtlException
     * @throws NodalFlowException
     *
     * @return array
     */
    public function fromCasesProvider()
    {
        $fromQuery = 'SELECT * FROM ' . self::FROM_TABLE . ' ORDER BY id ASC';
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
        $AfterTenFrom->setBatchSize(10);

        return [
            [
                'flow' => (new YaEtl)
                    ->from($fullFrom)
                    ->to($this->getLoaderMock()),
            ],
            [
                'flow' => (new YaEtl)
                    ->from(clone $fullFrom)
                    ->transform(new NoOpTransformer)
                    ->transform(new CallableTransformer(function ($record) {
                        return $record;
                    }))
                    ->to($this->getLoaderMock()),
            ],
            [
                'flow' => (new YaEtl)
                    ->from($FirstHalfFrom)
                    ->from($SecondHalfFrom, $FirstHalfFrom)
                    ->transform(new NoOpTransformer)
                    ->to($this->getLoaderMock()),
            ],
            [
                'flow' => (new YaEtl)
                    ->from($FirstTenFrom)
                    ->from($AfterTenFrom, $FirstTenFrom)
                    ->transform(new NoOpTransformer)
                    ->to($this->getLoaderMock()),
            ],
            [
                'flow' => (new YaEtl)
                    ->from(new PdoUniqueKeyExtractor($this->getPdo(), $fromQuery, 'id'))
                    ->to($this->getLoaderMock()),
            ],
            [
                'flow' => (new YaEtl)
                    ->from(clone $fullFrom)
                    ->transform(new NoOpTransformer)
                    ->to($this->getLoaderMock())
                    ->transform(new NoOpTransformer),
            ],
        ];
    }
}
