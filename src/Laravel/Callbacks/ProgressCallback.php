<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Laravel\Callbacks;

use fab2s\NodalFlow\Callbacks\CallbackAbstract;
use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;

/**
 * Class ProgressCallback
 */
class ProgressCallback extends CallbackAbstract
{
    /**
     * The Laravel Command object, as it does not make sense
     * to display CLI progress otherwise
     *
     * @var Command
     */
    protected $command;

    /**
     * The Laravel output object, extracted from the command object
     *
     * @var OutputStyle
     */
    protected $output;

    /**
     * The number of records
     *
     * @var int|null
     */
    protected $numRecords;

    /**
     * Progress modulo, should align with YaEtl's one
     *
     * @var int
     */
    protected $progressMod = 1;

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
     * @param int $numRecords
     *
     * @return static
     */
    public function setNumRecords(int $numRecords): self
    {
        $this->numRecords = $numRecords;

        return $this;
    }

    /**
     * Set Laravel's Command
     *
     * @param Command $command
     *
     * @return static
     */
    public function setCommand(Command $command): self
    {
        $this->command = $command;

        $this->output = $this->command->getOutput();

        return $this;
    }

    /**
     * Triggered when a Flow starts
     *
     * @param FlowInterface $flow
     */
    public function start(FlowInterface $flow)
    {
        $this->command->info('[YaEtl] Start');
        $this->output->progressStart($this->numRecords);
    }

    /**
     * Triggered when a Flow progresses,
     * eg exec once or generates once
     *
     * @param FlowInterface $flow
     * @param NodeInterface $node
     */
    public function progress(FlowInterface $flow, NodeInterface $node)
    {
        $this->output->progressAdvance($this->progressMod);
    }

    /**
     * Triggered when a Flow succeeds
     *
     * @param FlowInterface $flow
     */
    public function success(FlowInterface $flow)
    {
        $this->output->progressFinish();

        $flowStatus = $flow->getFlowStatus();
        if ($flowStatus->isDirty()) {
            $this->command->warn('[YaEtl] Dirty Success');
        } else {
            $this->command->info('[YaEtl] Clean Success');
        }
    }

    /**
     * Triggered when a Flow fails
     *
     * @param FlowInterface $flow
     */
    public function fail(FlowInterface $flow)
    {
        $this->command->error('[YaEtl] Failed');
    }
}
