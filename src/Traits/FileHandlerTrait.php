<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Traits;

use fab2s\NodalFlow\YaEtlException;

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

    /**
     * @param resource|string $input
     * @param string          $mode
     *
     * @throws YaEtlException
     *
     * @return $this
     */
    protected function initHandle($input, $mode)
    {
        if (is_resource($input)) {
            $this->handle = $input;
        } elseif (is_file($input)) {
            $this->handle = fopen($input, $mode);
            if (!$this->handle) {
                throw new YaEtlException('Handle could not be opened in mode:' . $mode);
            }
        } else {
            throw new YaEtlException('$input is either not a resource or not a file');
        }

        return $this;
    }
}
