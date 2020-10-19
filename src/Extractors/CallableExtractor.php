<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\PayloadNodeAbstract;

/**
 * Class CallableExtractor
 */
class CallableExtractor extends PayloadNodeAbstract implements ExtractorInterface
{
    /**
     * The underlying executable or traversable Payload
     *
     * @var callable
     */
    protected $payload;

    /**
     * @var array
     */
    protected $nodeIncrements = [
        'num_records' => 'num_iterate',
        'num_extract' => 0,
    ];

    /**
     * @var iterable
     */
    protected $extracted;

    /**
     * CallableExtractorAbstract constructor.
     *
     * @param callable $payload
     * @param bool     $isAReturningVal
     *
     * @throws NodalFlowException
     */
    public function __construct(callable $payload, bool $isAReturningVal = true)
    {
        parent::__construct($payload, $isAReturningVal, true);
    }

    /**
     * @param mixed|null $param
     *
     * @return bool false in case no more records can be fetched
     */
    public function extract($param = null): bool
    {
        $extracted = \call_user_func($this->payload, $param);

        if (!is_iterable($extracted)) {
            return false;
        }

        $this->extracted = $extracted;

        return true;
    }

    /**
     * get the traversable to traverse within the Flow
     *
     * @param mixed $param
     *
     * @return \Generator
     */
    public function getTraversable($param = null): iterable
    {
        if (!$this->extract($param)) {
            // we still return an empty generator here
            return;
        }

        if ($this->getCarrier()) {
            $this->getCarrier()->getFlowMap()->incrementNode($this->getId(), 'num_extract');
        }

        foreach ($this->extracted as $record) {
            yield $record;
        }
    }
}
