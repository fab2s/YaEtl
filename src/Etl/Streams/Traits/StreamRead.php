<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Etl\Streams\Traits;

use fab2s\YaEtl\YaEtlException;

trait StreamRead
{
    /**
     * @var string
     */
    protected $sourcePath = '';

    /**
     * @var string
     */
    protected $sourceFileName;

    /**
     * @var resource|null
     */
    protected $sourceStream = null;

    public function setSourcePath(string $sourcePath): self
    {
        $this->sourcePath = $this->slashPath($sourcePath);

        return $this;
    }

    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    public function setSourceStream($resource): self
    {
        if (!is_resource($resource)) {
            throw new YaEtlException('[' . class_basename(static::class) . '] Invalid handle');
        }

        $this->sourceStream = $resource;

        return $this;
    }

    /**
     * @return null|false|resource
     */
    public function getSourceStream(bool $fresh = false)
    {
        if (!$fresh && !empty($this->sourceStream)) {
            return $this->sourceStream;
        }

        return $this->sourceStream = fopen($this->getSourceFilePath(), 'rb');
    }

    public function getSourceFileName(): string
    {
        return $this->sourceFileName;
    }

    public function setSourceFileName($sourceFileName): self
    {
        $this->sourceFileName = basename($sourceFileName);

        return $this;
    }

    public function getSourceFilePath(): string
    {
        return $this->getSourcePath() . $this->getSourceFileName();
    }

    abstract public function slashPath(string $path) : string;
}
