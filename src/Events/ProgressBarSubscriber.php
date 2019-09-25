<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Laravel\Callbacks;

use fab2s\NodalFlow\Events\FlowEvent;
use fab2s\NodalFlow\Events\FlowEventInterface;
use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\YaEtl\YaEtl;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ProgressBarSubscriber
 */
class ProgressBarSubscriber implements EventSubscriberInterface
{
    /**
     * The Laravel output object, extracted from the command object
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * The number of records
     *
     * @var int|null
     */
    protected $numRecords;

    /**
     * Progress modulo, should be aligned with YaEtl's one
     *
     * @var int
     */
    protected $progressMod = 1024;

    /**
     * @var ProgressBar
     */
    protected $progressBar;

    /**
     * ProgressBarSubscriber constructor.
     *
     * @param YaEtl|null $flow
     *
     * @throws \ReflectionException
     */
    public function __construct(YaEtl $flow = null)
    {
        if ($flow !== null) {
            // auto register
            $this->registerFlow($flow);
        }
    }

    /**
     * @param YaEtl $flow
     *
     * @throws \ReflectionException
     *
     * @return static
     */
    public function registerFlow(YaEtl $flow): self
    {
        $flow->getDispatcher()->addSubscriber($this);

        return $this;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        if (!isset($this->output)) {
            $this->output = new ConsoleOutput;
        }

        return $this->output;
    }

    /**
     * @param OutputInterface $output
     *
     * @return static
     */
    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Set progress modulo
     *
     * @param int $progressMod
     *
     * @return static
     */
    public function setProgressMod(int $progressMod): self
    {
        $this->progressMod = max(1, (int) $progressMod);

        return $this;
    }

    /**
     * Set the total number of records prior to FLow execution
     *
     * @param int|null $numRecords
     *
     * @return static
     */
    public function setNumRecords(?int $numRecords): self
    {
        $this->numRecords = $numRecords;

        return $this;
    }

    /**
     * Triggered when a Flow starts
     *
     * @param FlowEventInterface $event
     */
    public function start(FlowEventInterface $event)
    {
        /** @var YaEtl $flow */
        $flow = $event->getFlow();
        $this->setProgressMod($flow->getProgressMod())
            ->getOutput()
            ->writeln('<info>[YaEtl] Start</info>');
        $this->progressBar = new ProgressBar($this->output);
        $this->progressBar->start($this->numRecords);
    }

    /**
     * Triggered when a Flow progresses,
     * eg exec once or generates once
     */
    public function progress()
    {
        $this->progressBar->advance($this->progressMod);
    }

    /**
     * Triggered when a Flow succeeds
     *
     * @param FlowEventInterface $event
     */
    public function success(FlowEventInterface $event)
    {
        $this->progressBar->finish();
        $this->output->writeln(PHP_EOL);
        $flow       = $event->getFlow();
        $flowStatus = $flow->getFlowStatus();
        if ($flowStatus->isDirty()) {
            $this->output->writeln('<warn>[YaEtl] Dirty Success</warn>');
        } else {
            $this->output->writeln('<info>[YaEtl] Clean Success</info>');
        }

        $this->displayReport($flow);
    }

    /**
     * Triggered when a Flow fails
     *
     * @param FlowEventInterface $event
     */
    public function fail(FlowEventInterface $event)
    {
        $this->progressBar->finish();
        $this->output->writeln(PHP_EOL);
        $this->output->writeln('<error>[YaEtl] Failed</error>');
        $this->displayReport($event->getFlow());
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FlowEvent::FLOW_START    => ['start', 0],
            FlowEvent::FLOW_PROGRESS => ['progress', 0],
            FlowEvent::FLOW_SUCCESS  => ['success', 0],
            FlowEvent::FLOW_FAIL     => ['fail', 0],
        ];
    }

    /**
     * @param FlowInterface $flow
     *
     * @return static
     */
    protected function displayReport(FlowInterface $flow): self
    {
        $flowStats = $flow->getStats();
        $this->output->writeln($flowStats['report']);

        return $this;
    }
}
