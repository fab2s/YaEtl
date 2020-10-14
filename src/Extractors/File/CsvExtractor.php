<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors\File;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\YaEtlException;
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
    public function __construct($input, string $delimiter = ',', string $enclosure = '"', string $escape = '\\')
    {
        parent::__construct($input);
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape    = $escape;
    }

    /**
     * @return iterable
     */
    protected function getExtracted(): iterable
    {
        if (!$this->readBom() || !$this->readSep() || false === ($firstRecord = $this->readHeader())) {
            return;
        }

        /* @var array $firstRecord */
        yield $this->bakeRecord($firstRecord);
        while (null !== ($record = $this->getNextNonEmptyRecord())) {
            /* @var array $record */
            yield $this->bakeRecord($record);
        }
    }

    /**
     * @param array $record
     *
     * @return array
     */
    protected function bakeRecord(array $record): array
    {
        return isset($this->header) ? array_combine($this->header, $record) : $record;
    }

    /**
     * @return array|null
     */
    protected function readHeader(): ?array
    {
        if (null === ($firstRecord = $this->getNextNonEmptyRecord())) {
            return null;
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
    protected function readSep(): bool
    {
        if (null === ($firstChar = $this->getNextNonEmptyChars())) {
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
     * @return array|null
     */
    protected function getNextNonEmptyRecord(): ? array
    {
        do {
            if (false === ($record = fgetcsv($this->handle, 0, $this->delimiter, $this->enclosure, $this->escape))) {
                return null;
            }

            if ($record === [null]) {
                // empty line
                continue;
            }

            return $record;
        } while (!feof($this->handle));
    }
}
