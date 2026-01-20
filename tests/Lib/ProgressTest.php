<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\Tests\Lib;

use Closure;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Events\ProgressBarSubscriber;
use fab2s\YaEtl\Extractors\CallableExtractor;
use fab2s\YaEtl\Qualifiers\LimitQualifier;
use fab2s\YaEtl\Transformers\NoOpTransformer;
use fab2s\YaEtl\YaEtl;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionException;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class ProgressTest
 */
class ProgressTest extends TestBase
{
    /**
     * @throws NodalFlowException
     * @throws ReflectionException
     */
    #[DataProvider('progressProvider')]
    public function test_progress(YaEtl $flow, ?int $numRecords, int $progressMod, array $expected)
    {
        $flow->setProgressMod($progressMod);
        $progressSubscriber = new ProgressBarSubscriber($flow);
        $progressSubscriber->setOutput(new StreamOutput(fopen('php://memory', 'r+', false)))
            ->setNumRecords($numRecords);
        $flow->exec();

        /** @var StreamOutput $output */
        $output  = $progressSubscriber->getOutput();
        $display = $this->getStreamContent($output->getStream());

        $this->assertNotEmpty($display);
        foreach ($expected['contains'] as $contain) {
            $this->assertStringContainsString($contain, $display);
        }
    }

    /**
     * @throws NodalFlowException
     */
    public static function progressProvider(): array
    {
        return [
            [
                (new YaEtl)->from(new CallableExtractor(self::getTraversableClosure(10)))
                    ->qualify(new LimitQualifier(5))
                    ->transform(new NoOpTransformer),
                15,
                10,
                [
                    'num_progress' => 1,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Dirty Success',
                        '[YaEtl](dirty) 1 Extractor - 1 Extract - 6 Record (6 Iterations)',
                    ],
                ],
            ],
            [
                (new YaEtl)->from(new CallableExtractor(self::getTraversableClosure(100)))
                    ->transform(new NoOpTransformer),
                100,
                10,
                [
                    'num_progress' => 11,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
                        '[YaEtl](clean) 1 Extractor - 1 Extract - 100 Record (100 Iterations)',
                    ],
                ],
            ],
            [
                (new YaEtl)->from(new CallableExtractor(self::getTraversableClosure(100)))
                    ->transform(new NoOpTransformer),
                null,
                10,
                [
                    'num_progress' => 11,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
                        '[YaEtl](clean) 1 Extractor - 1 Extract - 100 Record (100 Iterations)',
                    ],
                ],
            ],
            [
                (new YaEtl)->from(new CallableExtractor(self::getTraversableClosure(100)))
                    ->transform(new NoOpTransformer),
                1337,
                1024,
                [
                    'num_progress' => 1,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
                        '[YaEtl](clean) 1 Extractor - 1 Extract - 100 Record (100 Iterations)',
                    ],
                ],
            ],
            [
                (new YaEtl)->from(new CallableExtractor(self::getTraversableClosure(10)))
                    ->transform(new NoOpTransformer),
                15,
                10,
                [
                    'num_progress' => 2,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
                        '[YaEtl](clean) 1 Extractor - 1 Extract - 10 Record (10 Iterations)',
                    ],
                ],
            ],
        ];
    }

    /**
     * Gets the display returned by the last execution of the command or application.
     *
     * @param resource $stream
     * @param bool     $normalize Whether to normalize end of lines to \n or not
     *
     * @return string The display
     */
    protected function getStreamContent($stream, bool $normalize = false): string
    {
        rewind($stream);
        $display = stream_get_contents($stream);
        if ($normalize) {
            $display = str_replace(PHP_EOL, "\n", $display);
        }

        return $display;
    }

    protected function getNoOpClosure(): Closure
    {
        return function ($record) {
            return $record;
        };
    }
}
