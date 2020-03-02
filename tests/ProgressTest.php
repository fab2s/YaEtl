<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Extractors\CallableExtractor;
use fab2s\YaEtl\Laravel\Callbacks\ProgressBarSubscriber;
use fab2s\YaEtl\Transformers\NoOpTransformer;
use fab2s\YaEtl\YaEtl;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class ProgressTest
 */
class ProgressTest extends \TestBase
{
    /**
     * @dataProvider progressProvider
     *
     * @param YaEtl    $flow
     * @param int|null $numRecords
     * @param int      $progressMod
     * @param array    $expected
     *
     * @throws NodalFlowException
     * @throws ReflectionException
     */
    public function testProgress(YaEtl $flow, ?int $numRecords, int $progressMod, array $expected)
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
     *
     * @return array
     */
    public function progressProvider(): array
    {
        return [
            [
                'flow'     => (new YaEtl)->from(new CallableExtractor($this->getTraversableClosure(100)))
                    ->transform(new NoOpTransformer),
                'num_records'  => 100,
                'progress_mod' => 10,
                'expected'     => [
                    'num_progress' => 11,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
                        '[YaEtl](clean) 1 Extractor - 1 Extract - 100 Record (100 Iterations)',
                    ],
                ],
            ],
            [
                'flow'     => (new YaEtl)->from(new CallableExtractor($this->getTraversableClosure(100)))
                    ->transform(new NoOpTransformer),
                'num_records'  => null,
                'progress_mod' => 10,
                'expected'     => [
                    'num_progress' => 11,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
                        '[YaEtl](clean) 1 Extractor - 1 Extract - 100 Record (100 Iterations)',
                    ],
                ],
            ],
            [
                'flow'     => (new YaEtl)->from(new CallableExtractor($this->getTraversableClosure(100)))
                    ->transform(new NoOpTransformer),
                'num_records'  => 1337,
                'progress_mod' => 1024,
                'expected'     => [
                    'num_progress' => 1,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
                        '[YaEtl](clean) 1 Extractor - 1 Extract - 100 Record (100 Iterations)',
                    ],
                ],
            ],
            [
                'flow'     => (new YaEtl)->from(new CallableExtractor($this->getTraversableClosure(10)))
                    ->transform(new NoOpTransformer),
                'num_records'  => 15,
                'progress_mod' => 10,
                'expected'     => [
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
     * @param int $limit
     *
     * @return Closure
     */
    protected function getTraversableClosure($limit = 10): \Closure
    {
        return function () use ($limit) {
            for ($i = 1; $i <= $limit; ++$i) {
                yield $i;
            }
        };
    }
}
