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
 * Class ArrayReplaceTransformer
 */
class ArrayReplaceTransformer extends TransformerAbstract
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
     *
     * @throws NodalFlowException
     */
    public function __construct(array $default, array $override = [])
    {
        $this->default  = $default;
        $this->override = $override;
        parent::__construct();
    }

    /**
     * Set defaults and/or overrides in the record
     *
     * @param array $record
     *
     * @return array
     */
    public function exec($record = null)
    {
        return \array_replace($this->default, $record, $this->override);
    }
}
