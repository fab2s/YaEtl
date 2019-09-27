<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\Interrupter;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Extractors\CallableExtractor;
use fab2s\YaEtl\Qualifiers\CallableQualifier;
use fab2s\YaEtl\Transformers\NoOpTransformer;
use fab2s\YaEtl\YaEtl;

/**
 * Class QualifierTest
 */
class QualifierTest extends \TestBase
{
    /**
     * @dataProvider interruptProvider
     *
     * @param FlowInterface $flow
     * @param array         $expected
     */
    public function testInterrupt(FlowInterface $flow, array $expected)
    {
        $flow->exec();
        $this->interruptAssertions($flow->getNodeMap(), $expected);
    }

    /**
     * @throws NodalFlowException
     *
     * @return array
     */
    public function interruptProvider(): array
    {
        $breakAt5Node1    = new CallableQualifier($this->getBreakAt5Closure());
        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;

        $testCases          = [];
        $testCases['flow1'] = [
            'flow'     => (new YaEtl)->from($extractor1)
                ->transform($noOpTransformer1)
                ->qualify($breakAt5Node1)
                ->transform($noOpTransformer2),
            'expected' => [
                $extractor1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 5,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpTransformer1->getId() => [
                    'num_exec'     => 5,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $breakAt5Node1->getId() => [
                    'num_exec'     => 5,
                    'num_iterate'  => 0,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpTransformer2->getId() => [
                    'num_exec'     => 4,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $continueAt5Node1   = new CallableQualifier($this->getContinueAt5Closure());
        $extractor1         = new CallableExtractor($this->getTraversable10Closure());
        $noOpTransformer1   = new NoOpTransformer;
        $noOpTransformer2   = new NoOpTransformer;
        $testCases['flow2'] = [
            'flow'     => (new YaEtl)->from($extractor1)
                ->transform($noOpTransformer1)
                ->qualify($continueAt5Node1)
                ->transform($noOpTransformer2),
            'expected' => [
                $extractor1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 10,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $noOpTransformer1->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $continueAt5Node1->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 1,
                ],
                $noOpTransformer2->getId() => [
                    'num_exec'     => 9,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $breakAt5Node1    = new CallableQualifier($this->getBreakAt5Closure());
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->qualify($breakAt5Node1)
            ->transform($noOpTransformer2);

        $noOpTransformer3 = new NoOpTransformer;
        $noOpTransformer4 = new NoOpTransformer;

        $testCases['flow3'] = [
            'flow'     => (new YaEtl)->transform($noOpTransformer3)
                ->branch($branch1)
                ->transform($noOpTransformer4),
            'expected' => [
                $noOpTransformer3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 5,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 4,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $continueAt5Node1 = new CallableQualifier($this->getContinueAt5Closure());
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $branch1          = (new YaEtl())->from($extractor1)
            ->transform($noOpTransformer1)
            ->qualify($continueAt5Node1)
            ->transform($noOpTransformer2);

        $noOpTransformer3 = new NoOpTransformer;
        $noOpTransformer4 = new NoOpTransformer;

        $testCases['flow4'] = [
            'flow'     => (new YaEtl)->transform($noOpTransformer3)
                ->branch($branch1)
                ->transform($noOpTransformer4),
            'expected' => [
                $noOpTransformer3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 10,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 9,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $breakAt5Node1    = new CallableQualifier($this->getBreakAt5Closure());
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($breakAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;

        $testCases['flow5'] = [
            'flow'     => (new YaEtl)->transform($noOpTransformer4)
                ->branch($branch1)
                ->transform($noOpTransformer5),
            'expected' => [
                $noOpTransformer4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 10,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 95,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 95,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 95,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 94,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $continueAt5Node1 = new CallableQualifier($this->getContinueAt5Closure());
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($continueAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;

        $testCases['flow6'] = [
            'flow'     => (new YaEtl)->transform($noOpTransformer4)
                ->branch($branch1)
                ->transform($noOpTransformer5),
            'expected' => [
                $noOpTransformer4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 10,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 100,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 100,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 100,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 99,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $breakAt5Node1    = new CallableQualifier($this->getBreakAt5Closure(new Interrupter(InterrupterInterface::TARGET_SELF, $extractor1, InterrupterInterface::TYPE_BREAK)));
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($breakAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;

        $testCases['flow7'] = [
            'flow'     => (new YaEtl)->transform($noOpTransformer4)
                ->branch($branch1)
                ->transform($noOpTransformer5),
            'expected' => [
                $noOpTransformer4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 1,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 5,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 4,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $continueAt5Node1 = new CallableQualifier($this->getContinueAt5Closure(new Interrupter(InterrupterInterface::TARGET_SELF, $extractor1, InterrupterInterface::TYPE_CONTINUE)));
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($continueAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;

        $testCases['flow8'] = [
            'flow'     => (new YaEtl)->transform($noOpTransformer4)
                ->branch($branch1)
                ->transform($noOpTransformer5),
            'expected' => [
                $noOpTransformer4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 10,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 95,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 95,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 95,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 94,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new YaEtl;
        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $breakAt5Node1    = new CallableQualifier($this->getBreakAt5Closure(new Interrupter($rootFlow, null, InterrupterInterface::TYPE_BREAK)));
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($breakAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;

        $rootFlow->transform($noOpTransformer4)
            ->branch($branch1)
            ->transform($noOpTransformer5);

        $testCases['flow9'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $noOpTransformer4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 1,
                    'num_continue' => 0,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 1,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 5,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 4,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 0,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new YaEtl;
        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $continueAt5Node1 = new CallableQualifier($this->getContinueAt5Closure(new Interrupter($rootFlow, null, InterrupterInterface::TYPE_CONTINUE)));
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($continueAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;

        $rootFlow->transform($noOpTransformer4)
            ->branch($branch1)
            ->transform($noOpTransformer5);

        $testCases['flow10'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $noOpTransformer4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 1,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 1,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 5,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 4,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 0,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new YaEtl;
        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $extractor3       = new CallableExtractor($this->getTraversable10Closure());
        $extractor4       = new CallableExtractor($this->getTraversable10Closure());
        $breakAt5Node1    = new CallableQualifier($this->getBreakAt5Closure(new Interrupter($rootFlow, null, InterrupterInterface::TYPE_BREAK)));
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($breakAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;
        $noOpTransformer6 = new NoOpTransformer;

        $rootFlow->from($extractor3)
            ->transform($noOpTransformer6)
            ->from($extractor4)
            ->transform($noOpTransformer4)
            ->branch($branch1)
            ->transform($noOpTransformer5);

        $testCases['flow11'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $extractor3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 10,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $noOpTransformer6->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $extractor4->getId() => [
                    'num_exec'     => 10,
                    // the break signal is sent at rec n°5
                    // it is detected on the 1st records of
                    // this traversable which breaks there
                    // and get the upstream traversable 2nd
                    // rec and so on
                    'num_iterate'  => 91,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpTransformer4->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 1,
                    'num_continue' => 0,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 91,
                            'num_iterate'  => 901,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 9005,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 9004,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 90,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new YaEtl;
        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $extractor3       = new CallableExtractor($this->getTraversable10Closure());
        $extractor4       = new CallableExtractor($this->getTraversable10Closure());
        $continueAt5Node1 = new CallableQualifier($this->getBreakAt5Closure(new Interrupter($rootFlow, null, InterrupterInterface::TYPE_CONTINUE)));
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($continueAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;
        $noOpTransformer6 = new NoOpTransformer;

        $rootFlow->from($extractor3)
            ->transform($noOpTransformer6)
            ->from($extractor4)
            ->transform($noOpTransformer4)
            ->branch($branch1)
            ->transform($noOpTransformer5);

        $testCases['flow12'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $extractor3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 10,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $noOpTransformer6->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $extractor4->getId() => [
                    'num_exec'     => 10,
                    // the break signal is sent at rec n°5
                    // it is detected on the 1st records of
                    // this traversable which breaks there
                    // and get the upstream traversable 2nd
                    // rec and so on
                    'num_iterate'  => 100,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $noOpTransformer4->getId() => [
                    'num_exec'     => 100,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 100,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 1,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 100,
                            'num_iterate'  => 991,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 991,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 991,
                            'num_iterate'  => 9905,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 9905,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 9905,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 9904,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 99,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new YaEtl;
        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $extractor3       = new CallableExtractor($this->getTraversable10Closure());
        $extractor4       = new CallableExtractor($this->getTraversable10Closure());
        $breakAt5Node1    = new CallableQualifier($this->getBreakAt5Closure(new Interrupter($rootFlow, $extractor3, InterrupterInterface::TYPE_BREAK)));
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($breakAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;
        $noOpTransformer6 = new NoOpTransformer;

        $rootFlow->from($extractor3)
            ->transform($noOpTransformer6)
            ->from($extractor4)
            ->transform($noOpTransformer4)
            ->branch($branch1)
            ->transform($noOpTransformer5);

        $testCases['flow13'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $extractor3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 1,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpTransformer6->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $extractor4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 1,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpTransformer4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 1,
                    'num_continue' => 0,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 1,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 5,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 4,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 0,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new YaEtl;
        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $extractor3       = new CallableExtractor($this->getTraversable10Closure());
        $extractor4       = new CallableExtractor($this->getTraversable10Closure());
        $continueAt5Node1 = new CallableQualifier($this->getBreakAt5Closure(new Interrupter($rootFlow, $extractor3, InterrupterInterface::TYPE_CONTINUE)));
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($continueAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;
        $noOpTransformer6 = new NoOpTransformer;

        $rootFlow->from($extractor3)
            ->transform($noOpTransformer6)
            ->from($extractor4)
            ->transform($noOpTransformer4)
            ->branch($branch1)
            ->transform($noOpTransformer5);

        $testCases['flow14'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $extractor3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 10,
                    'num_break'    => 0,
                    'num_continue' => 1,
                ],
                $noOpTransformer6->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $extractor4->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 91,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpTransformer4->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 1,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 91,
                            'num_iterate'  => 901,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 9005,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 9004,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 90,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new YaEtl;
        $extractor1       = new CallableExtractor($this->getTraversable10Closure());
        $extractor2       = new CallableExtractor($this->getTraversable10Closure());
        $extractor3       = new CallableExtractor($this->getTraversable10Closure());
        $extractor4       = new CallableExtractor($this->getTraversable10Closure());
        $breakAt5Node1    = new CallableQualifier($this->getBreakAt5Closure(new Interrupter($rootFlow, $extractor4, InterrupterInterface::TYPE_BREAK)));
        $noOpTransformer1 = new NoOpTransformer;
        $noOpTransformer2 = new NoOpTransformer;
        $noOpTransformer3 = new NoOpTransformer;
        $branch1          = (new YaEtl)->from($extractor1)
            ->transform($noOpTransformer1)
            ->from($extractor2)
            ->transform($noOpTransformer2)
            ->qualify($breakAt5Node1)
            ->transform($noOpTransformer3);

        $noOpTransformer4 = new NoOpTransformer;
        $noOpTransformer5 = new NoOpTransformer;
        $noOpTransformer6 = new NoOpTransformer;

        $rootFlow->from($extractor3)
            ->transform($noOpTransformer6)
            ->from($extractor4)
            ->transform($noOpTransformer4)
            ->branch($branch1)
            ->transform($noOpTransformer5);

        $testCases['flow15'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $extractor3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 10,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $noOpTransformer6->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $extractor4->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 91,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpTransformer4->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branch1->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 1,
                    'num_continue' => 0,
                    'nodes'        => [
                        $extractor1->getId() => [
                            'num_exec'     => 91,
                            'num_iterate'  => 901,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer1->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $extractor2->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 9005,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer2->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpTransformer3->getId() => [
                            'num_exec'     => 9004,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpTransformer5->getId() => [
                    'num_exec'     => 90,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        return $testCases;
    }

    /**
     * @return Closure
     */
    protected function getNoOpClosure(): \Closure
    {
        return function ($record) {
            return $record;
        };
    }

    /**
     * @return Closure
     */
    protected function getTraversable10Closure(): \Closure
    {
        return function () {
            for ($i = 1; $i <= 10; ++$i) {
                yield $i;
            }
        };
    }

    /**
     * @param bool|InterrupterInterface $return
     *
     * @return Closure
     */
    protected function getBreakAt5Closure($return = null): \Closure
    {
        return function () use ($return) {
            static $cnt = 1;
            if ($cnt === 5) {
                ++$cnt;

                if ($return instanceof InterrupterInterface) {
                    return $return;
                }

                return new Interrupter(null, null, InterrupterInterface::TYPE_BREAK);
            }

            ++$cnt;

            return true;
        };
    }

    /**
     * @param bool|InterrupterInterface $return
     *
     * @return Closure
     */
    protected function getContinueAt5Closure($return = null): \Closure
    {
        return function () use ($return) {
            static $cnt = 1;
            if ($cnt === 5) {
                ++$cnt;

                if ($return instanceof InterrupterInterface) {
                    return $return;
                }

                return false;
            }

            ++$cnt;

            return true;
        };
    }

    /**
     * @param array $nodeMap
     * @param array $expected
     */
    protected function interruptAssertions(array $nodeMap, array $expected)
    {
        foreach ($nodeMap as $nodeId => $data) {
            if (stripos($data['class'], 'BranchNode') !== false) {
                // we need to get the branched flow id from
                // the "hidden" branch node instantiated by
                // YaEtl internally.
                // we do this quick'n *dirty* by just grabbing
                // the carrier id (Actual branch) from a node
                // in the branch node.
                $anyNodeInBranch = current($data['nodes']);
                $nodeId          = $anyNodeInBranch['flowId'];
            }

            $this->assertTrue(isset($expected[$nodeId]));
            if (isset($data['nodes'])) {
                $this->assertTrue(isset($expected[$nodeId]['nodes']));
                $this->interruptAssertions($data['nodes'], $expected[$nodeId]['nodes']);
            }

            unset($data['nodes'], $expected[$nodeId]['nodes']);

            $expected[$nodeId]['class'] = $data['class'];
            $expected[$nodeId]['hash']  = $data['hash'];
            $actual                     = array_intersect_key($data, $expected[$nodeId]);
            $this->assertSame(array_replace($actual, $expected[$nodeId]), $actual);
        }
    }
}
