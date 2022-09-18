<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib;

use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Extractors\CallableExtractor;
use fab2s\YaEtl\Loaders\ArrayLoader;
use fab2s\YaEtl\Qualifiers\LimitQualifier;
use fab2s\YaEtl\YaEtl;
use fab2s\YaEtl\YaEtlException;

/**
 * Class LimitQualifierTest
 */
class LimitQualifierTest extends TestBase
{
    /**
     * @dataProvider limitQualifierProvider
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function testLimitQualifier(array $expected, string $target)
    {
        $arrayLoader = new ArrayLoader;
        $yaEtl       = new YaEtl;
        $yaEtl->from(new CallableExtractor($this->getTraversableClosure(10)))
            ->qualify(new LimitQualifier(5, $target))
            ->to($arrayLoader)
            ->exec();

        $this->assertSame($expected, $arrayLoader->getLoadedData());
    }

    public function limitQualifierProvider(): array
    {
        $expected = range(1, 5);

        return [
            InterrupterInterface::TARGET_TOP => [
                $expected,
                InterrupterInterface::TARGET_TOP,
            ],
            InterrupterInterface::TARGET_SELF => [
                $expected,
                InterrupterInterface::TARGET_SELF,
            ],
        ];
    }

    /**
     * @dataProvider branchLimitQualifierProvider
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function testBranchLimitQualifier(array $expected, array $expectedBranch, string $target)
    {
        $arrayLoader       = new ArrayLoader;
        $branchArrayLoader = new ArrayLoader;
        $branch            = new YaEtl;
        $branch->qualify(new LimitQualifier(5, $target))
            ->to($branchArrayLoader);

        $yaEtl = new YaEtl;
        $yaEtl->from(new CallableExtractor($this->getTraversableClosure(10)))
            ->branch($branch)
            ->to($arrayLoader)
            ->exec();

        $this->assertSame($expected, $arrayLoader->getLoadedData());
        $this->assertSame($expectedBranch, $branchArrayLoader->getLoadedData());
    }

    public function branchLimitQualifierProvider(): array
    {
        $expectedBranch = range(1, 5);
        $expected       = range(1, 10);

        return [
            InterrupterInterface::TARGET_TOP => [
                $expectedBranch,
                $expectedBranch,
                InterrupterInterface::TARGET_TOP,
            ],
            InterrupterInterface::TARGET_SELF => [
                $expected,
                $expectedBranch,
                InterrupterInterface::TARGET_SELF,
            ],
        ];
    }
}
