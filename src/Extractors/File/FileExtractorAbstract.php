<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors\File;

use fab2s\Bom\Bom;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\YaEtlException;
use fab2s\YaEtl\Extractors\ExtractorAbstract;
use fab2s\YaEtl\Traits\FileHandlerTrait;

/**
 * Class FileExtractorAbstract
 */
abstract class FileExtractorAbstract extends ExtractorAbstract
{
    use FileHandlerTrait;

    /**
     * @param resource|string $input
     *
     * @throws YaEtlException
     * @throws NodalFlowException
     */
    public function __construct($input)
    {
        $this->initHandle($input, 'rb');

        parent::__construct();
    }

    /**
     * @param mixed $param
     *
     * @return bool
     */
    public function extract($param = null): bool
    {
        return !feof($this->handle);
    }

    /**
     * @return bool
     */
    protected function readBom(): bool
    {
        if (false === ($bomCandidate = fread($this->handle, 4))) {
            return false;
        }

        /* @var string $bomCandidate */
        if ($bom = Bom::extract($bomCandidate)) {
            $this->encoding = Bom::getBomEncoding($bom);

            return !fseek($this->handle, strlen($bom));
        }

        return rewind($this->handle);
    }

    /**
     * @return string|null
     */
    protected function getNextNonEmptyLine(): ?string
    {
        while (false !== ($line = fgets($this->handle))) {
            if ('' === ($line = trim($line))) {
                continue;
            }

            return $line;
        }

        return null;
    }

    /**
     * @return string|null
     */
    protected function getNextNonEmptyChars(): ? string
    {
        do {
            if (false === ($char = fread($this->handle, 1))) {
                return false;
            }
        } while (trim($char) === '');

        return $char ?? null;
    }
}
