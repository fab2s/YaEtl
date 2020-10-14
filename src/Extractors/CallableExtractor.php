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
     * This method is vaguely similar to a valid() meta iterator
     * It will triggers the record collection extraction when called
     * and return true when records where fetched.
     *
     * This is useful when batch extracting. If your extractor
     * does not perform batch extract (for example if you are
     * just reading a file line by line), just make so this method
     * triggers file open and return true in case of success and false
     * when called again.
     *
     * @param mixed|null $param
     *
     * @return bool false in case no more records can be fetched
     */
    public function extract($param = null): bool
    {
        $this->extracted = \call_user_func($this->payload, $param);

        if (!is_array($this->extracted) && !($this->extracted instanceof \Traversable)) {
            return false;
        }

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
            return;
        }

        $this->getCarrier()->getFlowMap()->incrementNode($this->getId(), 'num_extract');
        foreach ($this->extracted as $record) {
            yield $record;
        }
    }
}
