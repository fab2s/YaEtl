<?php

/*
 * This file is part of YaEtl.
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
     * @param mixed|resource|string $input
     *
     * @throws YaEtlException
     * @throws NodalFlowException
     */
    public function __construct($input)
    {
        $this->initHandle($input, 'wb');

        $metaData = stream_get_meta_data($this->handle);
        if (!is_writable($metaData['uri'])) {
            throw new YaEtlException('CsvLoader : destination cannot be opened in write mode');
        }

        parent::__construct();
    }
}
