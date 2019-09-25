<?php

/*
 * This file is part of YaEtl
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
     * @param resource|string $destination
     * @param string          $delimiter
     * @param string          $enclosure
     * @param string          $escape
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function __construct($destination, string $delimiter = ',', string $enclosure = '"', string $escape = '\\')
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
    public function exec($param = null)
    {
        $this->handleFirstLine($param)
            ->writeCsvLine($param);
    }

    /**
     * @return static
     */
    public function writeSep(): self
    {
        if ($this->useSep) {
            fwrite($this->handle, "sep=$this->delimiter" . PHP_EOL);
        }

        return $this;
    }

    /**
     * @param array $param
     *
     * @return static
     */
    public function writeHeader(array $param): self
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
     * @return static
     */
    protected function handleFirstLine($param): self
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
