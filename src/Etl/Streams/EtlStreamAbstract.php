<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Etl\Streams;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Etl\EtlAbstract;
use fab2s\YaEtl\Etl\Streams\Traits\SlashPath;
use fab2s\YaEtl\YaEtlException;
use ReflectionException;

abstract class EtlStreamAbstract extends EtlAbstract
{
    use SlashPath;

    /**
     * @throws NodalFlowException
     * @throws YaEtlException|ReflectionException
     *
     * @return $this
     */
    public function run(): EtlAbstract
    {
        parent::run();

        return $this->releaseStreams();
    }

    protected function releaseStreams(): EtlStreamAbstract
    {
        foreach (['sourceStream', 'destinationStream'] as $stream) {
            if (!empty($this->$stream)) {
                fclose($this->$stream);
                $this->$stream = null;
            }
        }

        return $this;
    }
}
