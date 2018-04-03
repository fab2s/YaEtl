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
use fab2s\YaEtl\Extractors\File\FileExtractorAbstract;
use fab2s\YaEtl\Traits\CsvHandlerTrait;

/**
 * Class CsvExtractor
 */
class CsvExtractor extends FileExtractorAbstract
{
    use CsvHandlerTrait;

    /**
     * CsvLoader constructor.
     *
     * @param resource|string $input
     * @param string|null     $delimiter
     * @param string|null     $enclosure
     * @param string|null     $escape
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function __construct($input, $delimiter = null, $enclosure = null, $escape = null)
    {
        parent::__construct($input);
        $this->initCsvOptions($delimiter, $enclosure, $escape);
    }

    /**
     * @param mixed $param
     *
     * @return \Generator
     */
    public function getTraversable($param = null)
    {
        if (!$this->extract($param)) {
            return;
        }

        if (false !== ($firstRecord = $this->getFirstRecord())) {
            /* @var array $firstRecord */
            yield $this->bakeRecord($firstRecord);
        }

        while (false !== ($record = fgetcsv($this->handle, 0, $this->delimiter, $this->enclosure, $this->escape))) {
            /* @var array $record */
            if (isset($this->header)) {
                $record = array_combine($this->header, $record);
            }

            yield $this->bakeRecord($record);
        }

        $this->releaseHandle();
    }

    /**
     * @param array $record
     *
     * @return array
     */
    protected function bakeRecord(array $record)
    {
        return isset($this->header) ? array_combine($this->header, $record) : $record;
    }

    /**
     * @return string|false
     */
    protected function getFirstRecord()
    {
        $useHeader = $this->useHeader;
        while (false !== ($line = fgets($this->handle))) {
            if ($line = trim($this->trimBom($line))) {
                // obey excel sep
                if (strpos($line, 'sep=') === 0) {
                    $this->useSep    = true;
                    $this->delimiter = $line[4];
                    continue;
                }

                $record = str_getcsv($line, $this->delimiter, $this->enclosure, $this->escape);
                if ($useHeader) {
                    $this->header = $record;
                    $useHeader    = false;
                    continue;
                }

                return $record;
            }
        }

        return false;
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
