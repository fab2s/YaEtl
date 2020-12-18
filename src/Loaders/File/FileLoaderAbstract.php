<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Loaders\File;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\YaEtlException;
use fab2s\YaEtl\Loaders\LoaderAbstract;
use fab2s\YaEtl\Traits\FileHandlerTrait;

/**
 * Class FileLoaderAbstract
 */
abstract class FileLoaderAbstract extends LoaderAbstract
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
        $this->checkHandle($input)
            ->initHandle($input, 'wb');
        parent::__construct();
    }

    /**
     * @return static
     */
    public function writeBom(): self
    {
        if ($this->useBom && ($bom = $this->prependBom(''))) {
            fwrite($this->handle, $bom);
        }

        return $this;
    }

    /**
     * @param resource|string $input
     *
     * @throws YaEtlException
     *
     * @return static
     */
    protected function checkHandle($input): self
    {
        if (is_resource($input)) {
            $metaData = stream_get_meta_data($input);
            if (!is_writable($metaData['uri'])) {
                throw new YaEtlException((new \ReflectionClass($this))->getShortName() . ' : destination cannot be opened in write mode');
            }

            return $this;
        }

        if (
            !is_string($input) ||
            (
                !is_file($input) &&
                !touch($input)
            )
        ) {
            throw new YaEtlException((new \ReflectionClass($this))->getShortName() . ' : destination cannot be created');
        }

        return $this;
    }
}
