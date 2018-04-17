<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors\File;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\YaEtlException;
use fab2s\OpinHelpers\Bom;
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
    public function extract($param = null)
    {
        $this->getCarrier()->getFlowMap()->incrementNode($this->getId(), 'num_extract');

        return !feof($this->handle);
    }

    /**
     * @return bool
     */
    protected function readBom()
    {
        if (false === ($buffer = $this->getNextNonEmptyChars())) {
            return false;
        }

        /* @var string $buffer */
        $firstCharPos = ftell($this->handle);
        if (false === ($chars = fread($this->handle, 3))) {
            return false;
        }

        /* @var string $chars */
        $buffer .= $chars;
        if ($bom = Bom::extract($buffer)) {
            $this->encoding = Bom::getBomEncoding($bom);

            return !fseek($this->handle, $firstCharPos + strlen($bom) - 1);
        }

        return !fseek($this->handle, $firstCharPos - 1);
    }

    /**
     * @return string|false
     */
    protected function getNextNonEmptyLine()
    {
        while (false !== ($line = fgets($this->handle))) {
            if ('' === ($line = trim($line))) {
                continue;
            }

            return $line;
        }

        return false;
    }

    /**
     * @return string|false
     */
    protected function getNextNonEmptyChars()
    {
        do {
            if (false === ($char = fread($this->handle, 1))) {
                return false;
            }
        } while (trim($char) === '');

        return $char;
    }
}
