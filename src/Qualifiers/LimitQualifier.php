<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Qualifiers;

use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\Interrupter;

/**
 * Class LimitQualifier
 */
class LimitQualifier extends QualifierAbstract
{
    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var string
     */
    protected $target;

    public function __construct(?int $limit = null, string $target = InterrupterInterface::TARGET_SELF)
    {
        parent::__construct();

        $this->setLimit($limit)
            ->setTarget($target);
    }

    /**
     * @param int|null $limit
     *
     * @return LimitQualifier
     */
    public function setLimit(?int $limit): LimitQualifier
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param string $target
     *
     * @return LimitQualifier
     */
    public function setTarget(string $target): LimitQualifier
    {
        $this->target = $target === InterrupterInterface::TARGET_TOP ? InterrupterInterface::TARGET_TOP : InterrupterInterface::TARGET_SELF;

        return $this;
    }

    public function qualify($param)
    {
        if ($this->limit && ++$this->count >= $this->limit + 1) {
            return new Interrupter($this->getTarget(), null, InterrupterInterface::TYPE_BREAK);
        }

        return true;
    }
}
