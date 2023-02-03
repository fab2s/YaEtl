<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Laravel\Etl\Streams\Traits;

use Illuminate\Filesystem\FilesystemAdapter;

trait DiskRead
{
    /**
     * @var FilesystemAdapter|null
     */
    protected $sourceDisk = null;

    /**
     * @var string
     */
    protected $sourceDiskPath = '';

    public function getSourceDisk(): ?FilesystemAdapter
    {
        return $this->sourceDisk;
    }

    public function setSourceDisk(?FilesystemAdapter $sourceDisk): self
    {
        $this->sourceDisk = $sourceDisk;

        return $this;
    }

    public function setSourceDiskPath(string $sourceDiskPath): self
    {
        $this->sourceDiskPath = $this->slashPath($sourceDiskPath);

        return $this;
    }

    public function getSourceDiskPath(): string
    {
        return $this->sourceDiskPath;
    }

    abstract public function slashPath(string $path) : string;
}
