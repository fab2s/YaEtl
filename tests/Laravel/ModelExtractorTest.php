<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Laravel;

use fab2s\YaEtl\Extractors\ExtractorAbstract;
use fab2s\YaEtl\Laravel\Extractors\ModelQueryExtractor;
use fab2s\YaEtl\YaEtlException;

class ModelExtractorTest extends LaravelTestCase
{
    use ExtractionTestTrait;

    public function testModelExtractorException()
    {
        $this->expectException(YaEtlException::class);
        (new ModelQueryExtractor)->setExtractQuery(null);
    }

    public function testModelExtractorExceptionType()
    {
        $this->expectException(YaEtlException::class);
        (new ModelQueryExtractor)->setExtractQuery(TestModel::getQuery());
    }

    protected function getTestQuery()
    {
        return TestModel::orderBy('id', 'asc');
    }

    protected function getExtractor(): ExtractorAbstract
    {
        return new ModelQueryExtractor($this->getTestQuery());
    }
}
