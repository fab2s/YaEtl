<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Laravel\Etl\Streams\Traits;

use Illuminate\Filesystem\FilesystemAdapter;

trait DiskWrite
{
    /**
     * @var FilesystemAdapter|null
     */
    protected $destinationDisk = null;

    /**
     * @var string
     */
    protected $destinationDiskPath = '';

    public function getDestinationDisk(): ?FilesystemAdapter
    {
        return $this->destinationDisk;
    }

    public function setDestinationDisk(?FilesystemAdapter $destinationDisk): self
    {
        $this->destinationDisk = $destinationDisk;

        return $this;
    }

    public function getDestinationDiskPath(): string
    {
        return $this->destinationDiskPath;
    }

    public function setDestinationDiskPath(string $destinationDiskPath): self
    {
        $this->destinationDiskPath = $this->slashPath($destinationDiskPath);

        return $this;
    }
    abstract public function slashPath(string $path) : string;
}
