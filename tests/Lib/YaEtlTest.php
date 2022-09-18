<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib;

use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\Interrupter;
use fab2s\YaEtl\Extractors\CallableExtractor;
use fab2s\YaEtl\Loaders\ArrayLoader;
use fab2s\YaEtl\Qualifiers\CallableQualifier;
use fab2s\YaEtl\Transformers\NoOpTransformer;
use fab2s\YaEtl\YaEtl;
use fab2s\YaEtl\YaEtlException;

/**
 * Class YaEtlTest
 */
class YaEtlTest extends TestBase
{
    public function testAddException()
    {
        $this->expectException(YaEtlException::class);
        (new YaEtl)->add(new NoOpTransformer);
    }

    public function testQualifyContinue()
    {
        $arrayLoader = new ArrayLoader;
        (new YaEtl)
            ->from(new CallableExtractor($this->getTraversableClosure(10)))
            ->qualify(new CallableQualifier(function ($value) {
                return $value <= 5;
            }))
            ->to($arrayLoader)
            ->exec();

        $this->assertSame(range(1, 5), $arrayLoader->getLoadedData());
    }

    public function testQualifyBreakInterrupter()
    {
        $arrayLoader = new ArrayLoader;
        (new YaEtl)->from(new CallableExtractor($this->getTraversableClosure(10)))
            ->qualify(new CallableQualifier(function ($value) {
                if ($value > 5) {
                    return new Interrupter(null, null, InterrupterInterface::TYPE_BREAK);
                }

                return true;
            }))
            ->to($arrayLoader)
            ->exec();

        $this->assertSame(range(1, 5), $arrayLoader->getLoadedData());
    }

    public function testQualifyBreakFlow()
    {
        $arrayLoader = new ArrayLoader;
        $yaEtl       = new YaEtl;
        $yaEtl->from(new CallableExtractor($this->getTraversableClosure(10)))
            ->qualify(new CallableQualifier(function ($value) use ($yaEtl) {
                if ($value > 5) {
                    $yaEtl->breakFlow();
                    // return value is of no consequences here
                    return true;
                }

                return true;
            }))
            ->to($arrayLoader)
            ->exec();

        $this->assertSame(range(1, 5), $arrayLoader->getLoadedData());
    }

    public function testQualifyBranch()
    {
        $branchLoader = new ArrayLoader;
        $arrayLoader  = new ArrayLoader;
        $branch       = (new YaEtl)
            ->qualify(new CallableQualifier(function ($value) {
                return $value <= 5;
            }))
            ->to($branchLoader);

        (new YaEtl)->from(new CallableExtractor($this->getTraversableClosure(10)))
            ->branch($branch)
            ->to($arrayLoader)
            ->exec();

        $this->assertSame(range(1, 5), $branchLoader->getLoadedData());
        $this->assertSame(range(1, 10), $arrayLoader->getLoadedData());
    }

    public function testAggregateToException()
    {
        $this->expectException(YaEtlException::class);
        $firstExtractor  = new CallableExtractor($this->getTraversableClosure(5));
        $secondExtractor = new CallableExtractor($this->getTraversableClosure(5, 6));

        (new YaEtl)->from($firstExtractor, $secondExtractor);
    }

    public function testAggregateToSingle()
    {
        $arrayLoader     = new ArrayLoader;
        $firstExtractor  = new CallableExtractor($this->getTraversableClosure(5));
        $secondExtractor = new CallableExtractor($this->getTraversableClosure(5, 6));

        (new YaEtl)->from($firstExtractor)
            ->from($secondExtractor, $firstExtractor)
            ->to($arrayLoader)
            ->exec();

        $this->assertSame(range(1, 10), $arrayLoader->getLoadedData());
    }

    public function testAggregateToMultiple()
    {
        $arrayLoader     = new ArrayLoader;
        $firstExtractor  = new CallableExtractor($this->getTraversableClosure(5));
        $secondExtractor = new CallableExtractor($this->getTraversableClosure(5, 6));
        $thirdExtractor  = new CallableExtractor($this->getTraversableClosure(5, 11));

        (new YaEtl)->from($firstExtractor)
            ->from($secondExtractor, $firstExtractor)
            ->from($thirdExtractor, $firstExtractor)
            ->to($arrayLoader)
            ->exec();

        $this->assertSame(range(1, 15), $arrayLoader->getLoadedData());
    }
}
