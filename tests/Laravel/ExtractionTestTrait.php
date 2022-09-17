<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Laravel;

use fab2s\YaEtl\Extractors\ExtractorAbstract;
use fab2s\YaEtl\Loaders\ArrayLoader;
use fab2s\YaEtl\YaEtl;

trait ExtractionTestTrait
{
    public function testExtraction()
    {
        $loader = new ArrayLoader;
        (new YaEtl)->from($this->getExtractor())
            ->to($loader)
            ->exec();
        // we must assertEquals as Laravel will output string type for int up to some version
        $this->assertEquals($this->getExpectedTestModelData(), $loader->getLoadedData());
    }

    public function testGetTraversable()
    {
        // we must assertEquals as Laravel will output string type for int up to some version
        $this->assertEquals($this->getExpectedTestModelData(), iterator_to_array($this->getExtractor()->getTraversable()));
    }

    abstract public static function assertEquals($expected, $actual, string $message = '');

    abstract protected function getExtractor(): ExtractorAbstract;

    abstract protected function getExpectedTestModelData(): array;
}
