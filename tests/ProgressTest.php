<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Extractors\CallableExtractor;
use fab2s\YaEtl\Laravel\Callbacks\ProgressBarSubscriber;
use fab2s\YaEtl\Transformers\NoOpTransformer;
use fab2s\YaEtl\YaEtl;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class ProgressTest
 */
class ProgressTest extends \TestCase
{
    /**
     * @dataProvider progressProvider
     *
     * @param NodalFlow $flow
     * @param int|null  $limit
     * @param int       $progressMod
     * @param array     $expected
     *
     * @throws NodalFlowException
     */
    public function testProgress(NodalFlow $flow, $limit, $progressMod, array $expected)
    {
        $progressSubscriber = new ProgressBarSubscriber;
        $progressSubscriber->setOutput(new StreamOutput(fopen('php://memory', 'r+', false)))
            ->setProgressMod($progressMod)
            ->setNumRecords($limit);
        /** @var StreamOutput $output */
        $output = $progressSubscriber->getOutput();

        $flow->setProgressMod($progressMod)->getDispatcher()->addSubscriber($progressSubscriber);
        $flow->exec();

        $display = $this->getStreamContent($output->getStream());

        $this->assertNotEmpty($display);

        foreach ($expected['contains'] as $contain) {
            $this->assertContains($contain, $display);
        }

        $limit = $limit ?: 100;
        $this->assertSame((int) ($limit / $progressMod) + 1, preg_match_all('`^\s*[0-9]+(?:/[0-9]+)? \[.*?\].*$`m', $display));
    }

    /**
     * @throws NodalFlowException
     *
     * @return array
     */
    public function progressProvider()
    {
        return [
            [
                'flow'     => (new YaEtl)->from(new CallableExtractor($this->getTraversableClosure(100)))
                    ->transform(new NoOpTransformer),
                'limit'        => 100,
                'progress_mod' => 10,
                'expected'     => [
                    'num_progress' => 11,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
                    ],
                ],
            ],
            [
                'flow'     => (new YaEtl)->from(new CallableExtractor($this->getTraversableClosure(100)))
                    ->transform(new NoOpTransformer),
                'limit'        => null,
                'progress_mod' => 10,
                'expected'     => [
                    'num_progress' => 11,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
                    ],
                ],
            ],
            [
                'flow'     => (new YaEtl)->from(new CallableExtractor($this->getTraversableClosure(100)))
                    ->transform(new NoOpTransformer),
                'limit'        => null,
                'progress_mod' => 1024,
                'expected'     => [
                    'num_progress' => 1,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
                    ],
                ],
            ],
            [
                'flow'     => (new YaEtl)->from(new CallableExtractor($this->getTraversableClosure(10)))
                    ->transform(new NoOpTransformer),
                'limit'        => 10,
                'progress_mod' => 10,
                'expected'     => [
                    'num_progress' => 2,
                    'contains'     => [
                        '[YaEtl] Start',
                        '[YaEtl] Clean Success',
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
    protected function getStreamContent($stream, $normalize = false)
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
    protected function getNoOpClosure()
    {
        return function ($record) {
            return $record;
        };
    }

    /**
     * @param mixed $limit
     *
     * @return Closure
     */
    protected function getTraversableClosure($limit = 10)
    {
        return function () use ($limit) {
            for ($i = 1; $i <= $limit; ++$i) {
                yield $i;
            }
        };
    }
}
