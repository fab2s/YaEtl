<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Traits;

/**
 * Trait FileHandlerTrait
 */
trait FileHandlerTrait
{
    /**
     * @var string UTF8 | UTF16_BE | UTF16_LE | UTF32_BE | UTF32_LE
     */
    protected $bomRegEx = '\xEF\xBB\xBF|\xFE\xFF|\xFF\xFE|\x00\x00\xFE\xFF|\xFF\xFE\x00\x00';

    /**
     * @var resource
     */
    protected $handle;

    /**
     * make sure we do not hold un-necessary handles
     */
    public function __destruct()
    {
        $this->releaseHandle();
    }

    /**
     * @param $string
     *
     * @return string
     */
    public function trimBom($string)
    {
        return preg_replace('`^' . $this->bomRegEx . '`', '', $string);
    }

    /**
     * release handle
     *
     * @return $this
     */
    public function releaseHandle()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }

        $this->handle = null;

        return $this;
    }
}
