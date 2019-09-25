<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Loaders;

/**
 * Class NoOpLoader
 */
class NoOpLoader extends LoaderAbstract
{
    /**
     * Execute the dumbest loader
     *
     * @param mixed $record
     *
     * @return bool
     */
    public function exec($record = null)
    {
        return !empty($record);
    }
}
