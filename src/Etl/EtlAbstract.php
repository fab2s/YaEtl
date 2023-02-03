<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Etl;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Events\ProgressBarSubscriber;
use fab2s\YaEtl\YaEtl;
use fab2s\YaEtl\YaEtlException;
use ReflectionException;

abstract class EtlAbstract
{
    /**
     * @var int|null
     */
    protected $limit = null;

    /**
     * The immutable time starting from when getNow or setNow are called
     *
     * @var DateTimeImmutable
     */
    protected $now;

    /**
     * @var YaEtl
     */
    protected $etl;

    /**
     * @var bool
     */
    protected $activateProgressBar = false;

    /**
     * @var mixed|null
     */
    protected $etlResult;

    /**
     * @throws NodalFlowException
     * @throws YaEtlException|ReflectionException
     *
     * @return $this
     */
    public function run(): self
    {
        $this->etlResult = null;
        $etl             = $this->getEtl();
        if ($this->activateProgressBar) {
            new ProgressBarSubscriber($etl);
        }

        if ($this->getLimit()) {
            $etl->limit($this->getLimit());
        }

        $this->etlResult = $etl->exec();

        return $this;
    }

    public function getEtlResult()
    {
        return $this->etlResult;
    }

    public function activateProgressBar(bool $activateProgressBar = true): self
    {
        $this->activateProgressBar = $activateProgressBar;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getNow(): DateTimeImmutable
    {
        if (isset($this->now)) {
            return $this->now;
        }

        return $this->now = new DateTimeImmutable('@' . time());
    }

    public function setNow(DateTimeInterface $now): self
    {
        $this->now = $now instanceof DateTimeImmutable ? $now : DateTimeImmutable::createFromMutable($now);

        return $this;
    }

    /**
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    abstract protected function getEtl(): YaEtl;
}
