<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Laravel;

use fab2s\YaEtl\Extractors\ExtractorAbstract;
use fab2s\YaEtl\Laravel\Extractors\DbExtractor;

class DbExtractorTest extends LaravelTestCase
{
    use ExtractionTestTrait;

    protected function getTestQuery()
    {
        return TestModel::getQuery()->orderBy('id', 'asc');
    }

    protected function getExtractor(): ExtractorAbstract
    {
        return new DbExtractor($this->getTestQuery());
    }
}