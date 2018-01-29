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

/**
 * Class FileExtractorAbstract
 */
abstract class FileExtractorAbstract extends ExtractorAbstract
{
    /**
     * @var string
     */
    protected $srcFile;

    /**
     * @var resource
     */
    protected $handle;

    /**
     * @param mixed|resource|string $input
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
     * make sure we do not hold un-necessary handles
     */
    public function __destruct()
    {
        $this->releaseHandle();
    }

    /**
     * @param mixed $param
     *
     * @return bool
     */
    public function extract($param = null)
    {
        if ($this->srcFile !== null) {
            $this->handle = fopen($this->srcFile, 'rb');
        }

        if (!$this->handle || !is_resource($this->handle)) {
            return false;
        }

        return rewind($this->handle);
    }

    /**
     * release handle
     *
     * @return $this
     */
    public function releaseHandle()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }

        $this->handle = null;

        return $this;
    }
}
