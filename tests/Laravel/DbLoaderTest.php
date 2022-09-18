<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Laravel;

use fab2s\YaEtl\Extractors\CallableExtractor;
use fab2s\YaEtl\Laravel\Extractors\DbExtractor;
use fab2s\YaEtl\Laravel\Loaders\DbLoader;
use fab2s\YaEtl\Loaders\ArrayLoader;
use fab2s\YaEtl\YaEtl;

class DbLoaderTest extends LaravelTestCase
{
    public function testDbLoader()
    {
        $this->createTestJoinModelTable();
        $loadQuery   = TestJoinModel::getQuery();
        $dbLoader    = new DbLoader($loadQuery);
        $arrayLoader = new ArrayLoader;
        (new YaEtl)
            ->from(new CallableExtractor(function () {
                return $this->getTestJoinModelSeedData();
            }))
            ->to($dbLoader)
            ->exec();

        (new YaEtl)
            ->from(new DbExtractor($loadQuery->orderBy('id')))
            ->to($arrayLoader)
            ->exec();

        $loadedData = $arrayLoader->getLoadedData();
        $this->assertEquals($this->getExpectedTestJoinModelData(), $loadedData);

        // now go through the update branch
        $loadQuery   = TestJoinModel::getQuery();
        $dbLoader    = (new DbLoader)->setLoadQuery($loadQuery);
        $arrayLoader = new ArrayLoader;
        $dbLoader->setWhereFields(['id']);
        $loadedData[0]['join'] = 'updated';

        (new YaEtl)
            ->from(new CallableExtractor(function () use ($loadedData) {
                return $loadedData;
            }))
            ->to($dbLoader)
            ->exec();

        (new YaEtl)
            ->from(new DbExtractor($loadQuery->orderBy('id')))
            ->to($arrayLoader)
            ->exec();

        $this->assertEquals($loadedData, $arrayLoader->getLoadedData());
    }
}
