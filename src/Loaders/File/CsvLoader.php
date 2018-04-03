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
     * @param string      $destination
     * @param string|null $delimiter
     * @param string|null $enclosure
     * @param string|null $escape
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function __construct($destination, $delimiter = null, $enclosure = null, $escape = null)
    {
        parent::__construct($destination);
        $this->initCsvOptions($delimiter, $enclosure, $escape);
    }

    /**
     * Execute this Node
     *
     * @param array $param
     */
    public function exec($param)
    {
        // take care about header etc
        if ($this->isFirstLine) {
            if ($this->useSep) {
                fwrite($this->handle, "sep=$this->delimiter\n");
            }

            if ($this->useHeader) {
                if (!isset($this->header)) {
                    $this->header = array_keys($param);
                }

                $this->writeCsvLine($this->header);
            }

            $this->isFirstLine = false;
        }

        $this->writeCsvLine($param);
    }

    /**
     * @param array $record
     *
     * @return bool|int
     */
    protected function writeCsvLine(array $record)
    {
        return fputcsv($this->handle, $record, $this->delimiter, $this->enclosure, $this->escape);
    }
}
