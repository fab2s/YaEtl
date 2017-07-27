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
 * Class ArrayReplaceRecursiveTransformer
 */
class ArrayReplaceRecursiveTransformer extends TransformerAbstract
{
    /**
     * @var array
     */
    protected $default;

    /**
     * @var array
     */
    protected $override;

    /**
     * @param array $default  An array of the default field values to use, if any
     * @param array $override An array of the field to always set to the same value, if any
     */
    public function __construct(array $default, array $override = [])
    {
        $this->default  = $default;
        $this->override = $override;
    }

    /**
     * Set defaults and/or overrides recursively in the record
     *
     * @param mixed $record
     *
     * @return mixed
     */
    public function exec($record)
    {
        return \array_replace_recursive($this->default, $record, $this->override);
    }
}
