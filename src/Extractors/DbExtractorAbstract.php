<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors;

/**
 * abstract Class DbExtractorAbstract
 */
abstract class DbExtractorAbstract extends ExtractorBatchLimitAbstract
{
    /**
     * @var \SplDoublyLinkedList|array
     */
    protected $extracted;

    /**
     * query
     *
     * @var mixed
     */
    protected $extractQuery;

    /**
     * @param mixed $extractQuery
     */
    public function __construct($extractQuery = null)
    {
        if ($extractQuery !== null) {
            $this->setExtractQuery($extractQuery);
        }
    }

    /**
     * @param mixed $param
     *
     * @return bool
     */
    public function extract($param = null)
    {
        if ($this->isLimitReached()) {
            return false;
        }

        $this->enforceBatchSize();
        if ($this->fetchRecords()) {
            $this->incrementOffset();

            return true;
        }

        return false;
    }

    /**
     * @param mixed $extractQuery
     *
     * @return $this
     */
    public function setExtractQuery($extractQuery)
    {
        $this->extractQuery = $extractQuery;

        return $this;
    }

    /**
     * @param mixed $param
     *
     * @return \Generator
     */
    public function getTraversable($param = null)
    {
        /*
         * unfortunately, we can't do something a simple as
         * while ($this->extracted->valid()) {
         *     yield $this->extracted->shift();
         * }
         *
         * @SEE https://php.net/spldoublylinkedlist.shift#120715
         *
         * Now since using shift() will result in an empty
         * SplDoublyLinkedList at the end of the extraction cycle,
         * the ETL will end up using less RAM when using multiple froms.
         * Otherwise, each extractor would keep its entire last
         * extracted collection in RAM until the end of the whole ETL.
         *
         * Besides, while the bellow code may "look" slower than :
         * foreach ($this->extracted as $record) {
         *     yield $record;
         * }
         *
         * we can't really say it is.
         * I quickly measured the time cost of this do/while vs foreach :
         * => 1M records from two extractors (500K each, 50K records per extract)
         * ==> php 7.1.2
         *      - foreach : 2sec 125ms - Memory: 74.00MiB
         *      - do / while : 1sec 922ms - Memory: 42.00MiB
         * ==> php 5.6.30
         *      - foreach : 2sec 686ms - Memory: 147.75MiB
         *      - do / while : 2sec 988ms - Memory: 80.00MiB
         *
         * still win-win ^^
         *
         */
        while ($this->extract($param)) {
            ++$this->numExtract;
            do {
                $record = $this->extracted->shift();
                $this->extracted->rewind();
                ++$this->numRecords;
                yield $record;
            } while ($this->extracted->valid());
        }
    }

    /**
     * @return string
     */
    protected function getLimitOffsetBit()
    {
        return ' ' . \implode('', [
            ' LIMIT ' . (int) $this->batchSize,
            $this->offset ? ' OFFSET ' . (int) $this->offset : '',
        ]);
    }

    /**
     * execute query and store results in $this->extracted
     *
     * @return bool true if there are records fetched
     */
    abstract protected function fetchRecords();
}
