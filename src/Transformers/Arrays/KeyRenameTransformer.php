<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Transformers\Arrays;

use fab2s\NodalFlow\NodalFlowException;
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
     *
     * @throws NodalFlowException
     */
    public function __construct(array $aliases)
    {
        $this->aliases = $aliases;
        parent::__construct();
    }

    /**
     * Replace keys in array
     * This method does not preserve incoming order
     *
     * @param array $record
     *
     * @return array
     */
    public function exec($record = null)
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
