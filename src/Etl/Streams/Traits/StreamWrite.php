<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Etl\Streams\Traits;

trait StreamWrite
{
    /**
     * @var string
     */
    protected $destinationPath = '';

    /**
     * @var string
     */
    protected $destinationFileName;

    /**
     * @var resource|null
     */
    protected $destinationStream = null;

    public function getDestinationFileName(): string
    {
        if (!isset($this->destinationFileName)) {
            if (is_callable([$this, 'getSourceFileName'])) {
                return $this->destinationFileName = $this->getSourceFileName();
            }
        }

        return $this->destinationFileName;
    }

    public function setDestinationFileName(string $destinationFileName): self
    {
        $this->destinationFileName = basename($destinationFileName);

        return $this;
    }
    /**
     * @return null|false|resource
     */
    public function getDestinationStream(bool $fresh = false)
    {
        if (!$fresh && !empty($this->destinationStream)) {
            return $this->destinationStream;
        }

        return $this->destinationStream = fopen($this->getDestinationFilePath(), 'wb');
    }

    public function getDestinationFilePath(): string
    {
        return $this->getDestinationPath() . $this->getDestinationFileName();
    }

    public function getDestinationPath(): string
    {
        return $this->destinationPath;
    }

    public function setDestinationPath(string $destinationPath): self
    {
        $this->destinationPath =  $this->slashPath($destinationPath);

        return $this;
    }

    abstract public function slashPath(string $path) : string;
}
