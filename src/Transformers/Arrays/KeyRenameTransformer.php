<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Transformers\Arrays;

use fab2s\YaEtl\Transformers\TransformerAbstract;

/**
 * Class KeyRenameTransformer
 */
class KeyRenameTransformer extends TransformerAbstract
{
    /**
     * array[inputKeyName] = 'outputKeyName'
     *
     * @var array
     */
    protected $aliases;

    /**
     * @param array $aliases
     */
    public function __construct(array $aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * Replace keys in array
     * This method does not preserve incoming order
     *
     * @param mixed $record
     *
     * @return mixed
     */
    public function exec($record)
    {
        foreach ($this->aliases as $inputKeyName => $outputKeyName) {
            if (\array_key_exists($inputKeyName, $record)) {
                $record[$outputKeyName] = $record[$inputKeyName];
                unset($record[$inputKeyName]);
            }
        }

        return $record;
    }
}
