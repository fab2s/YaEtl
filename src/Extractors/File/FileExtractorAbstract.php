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
use fab2s\YaEtl\Extractors\ExtractorAbstract;
use fab2s\YaEtl\Traits\FileHandlerTrait;

/**
 * Class FileExtractorAbstract
 */
abstract class FileExtractorAbstract extends ExtractorAbstract
{
    use FileHandlerTrait;

    /**
     * @var string
     */
    protected $srcFile;

    /**
     * @param resource|string $input
     *
     * @throws YaEtlException
     * @throws NodalFlowException
     */
    public function __construct($input)
    {
        if (is_resource($input)) {
            $this->handle = $input;
        } elseif (is_file($input)) {
            $this->srcFile = $input;
        } else {
            throw new YaEtlException('Input is either not a resource or not a file');
        }

        parent::__construct();
    }

    /**
     * @param mixed $param
     *
     * @throws YaEtlException
     *
     * @return bool
     */
    public function extract($param = null)
    {
        if ($this->srcFile !== null) {
            if (!($this->handle = fopen($this->srcFile, 'rb'))) {
                throw new YaEtlException('Cannot open file in read mode');
            }
        }

        if (!is_resource($this->handle)) {
            return false;
        }

        $this->getCarrier()->getFlowMap()->incrementNode($this->getId(), 'num_extract');

        return rewind($this->handle);
    }
}
