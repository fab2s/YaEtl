<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Loaders\File;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\YaEtlException;
use fab2s\YaEtl\Traits\CsvHandlerTrait;

/**
 * Class CsvLoader
 */
class CsvLoader extends FileLoaderAbstract
{
    use CsvHandlerTrait;

    /**
     * @var bool
     */
    protected $isFirstLine = true;

    /**
     * CsvLoader constructor.
     *
     * @param string $destination
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function __construct($destination, $delimiter = ',', $enclosure = '"', $escape = '\\')
    {
        parent::__construct($destination);
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape    = $escape;
    }

    /**
     * Execute this Node
     *
     * @param array $param
     */
    public function exec($param)
    {
        $this->handleFirstLine($param)
            ->writeCsvLine($param);
    }

    /**
     * @return $this
     */
    public function writeSep()
    {
        if ($this->useSep) {
            fwrite($this->handle, "sep=$this->delimiter" . PHP_EOL);
        }

        return $this;
    }

    /**
     * @param array $param
     *
     * @return $this
     */
    public function writeHeader(array $param)
    {
        if ($this->useHeader) {
            if (!isset($this->header)) {
                $this->header = array_keys($param);
            }

            $this->writeCsvLine($this->header);
        }

        return $this;
    }

    /**
     * @param array $record
     *
     * @return bool|int
     */
    public function writeCsvLine(array $record)
    {
        return fputcsv($this->handle, $record, $this->delimiter, $this->enclosure, $this->escape);
    }

    /**
     * @param array $param
     *
     * @return $this
     */
    protected function handleFirstLine($param)
    {
        if ($this->isFirstLine) {
            $this->writeBom()
                ->writeSep()
                ->writeHeader($param);
            $this->isFirstLine = false;
        }

        return $this;
    }
}
