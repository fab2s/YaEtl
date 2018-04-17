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
     * CsvExtractor constructor
     *
     * @param resource|string $input
     * @param string          $delimiter
     * @param string          $enclosure
     * @param string          $escape
     *
     * @throws NodalFlowException
     * @throws YaEtlException
     */
    public function __construct($input, $delimiter = ',', $enclosure = '"', $escape = '"')
    {
        parent::__construct($input);
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape    = $escape;
    }

    /**
     * @param mixed $param
     *
     * @return \Generator
     */
    public function getTraversable($param = null)
    {
        while ($this->extract($param)) {
            if (!$this->readBom() || !$this->readSep() || false === ($firstRecord = $this->readHeader())) {
                return;
            }

            /* @var array $firstRecord */
            yield $this->bakeRecord($firstRecord);
            while (false !== ($record = fgetcsv($this->handle, 0, $this->delimiter, $this->enclosure, $this->escape))) {
                /* @var array $record */
                yield $this->bakeRecord($record);
            }
        }

        $this->releaseHandle();
    }

    /**
     * @param array $record
     *
     * @return array
     */
    protected function bakeRecord($record)
    {
        return isset($this->header) ? array_combine($this->header, $record) : $record;
    }

    /**
     * @return bool
     */
    protected function readHeader()
    {
        if (false === ($firstRecord = $this->getNextNonEmptyRecord())) {
            return false;
        }

        if ($this->useHeader && !isset($this->header)) {
            $this->header = array_map('trim', $firstRecord);

            return $this->getNextNonEmptyRecord();
        }

        return $firstRecord;
    }

    /**
     * @return bool
     */
    protected function readSep()
    {
        if (false === ($firstChar = $this->getNextNonEmptyChars())) {
            return false;
        }

        $firstCharPos = ftell($this->handle);
        /* @var string $firstChar */
        if ($firstChar === 's') {
            if (false === ($chars = fread($this->handle, 4))) {
                return false;
            }

            /* @var string $chars */
            $line = $firstChar . $chars;

            if (strpos($line, 'sep=') === 0) {
                $this->useSep    = true;
                $this->delimiter = $line[4];

                return !fseek($this->handle, $firstCharPos + 5);
            }
        }

        return !fseek($this->handle, $firstCharPos - 1);
    }

    /**
     * @return array|false
     */
    protected function getNextNonEmptyRecord()
    {
        $lastPos = ftell($this->handle);
        do {
            $record = fgetcsv($this->handle, 0, $this->delimiter, $this->enclosure, $this->escape);
            // since it is unclear if all PHP versions do return false, empty array or array(null)
            // for blank lines, we won't rely on false for EOF
            if (empty($record) || $record === [null]) {
                // at least make sure we go forward
                $pos = ftell($this->handle);
                if (feof($this->handle) || $pos <= $lastPos) {
                    return false;
                }

                $lastPos = $pos;
                continue;
            }

            return $record;
        } while (!feof($this->handle));
    }
}
