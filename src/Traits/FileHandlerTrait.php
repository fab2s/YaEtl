<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Traits;

use fab2s\Bom\Bom;
use fab2s\NodalFlow\YaEtlException;

/**
 * Trait FileHandlerTrait
 */
trait FileHandlerTrait
{
    /**
     * @var resource
     */
    protected $handle;

    /**
     * @var string
     */
    protected $encoding;

    /**
     * @var bool
     */
    protected $useBom;

    /**
     * make sure we do not hold un-necessary handles
     */
    public function __destruct()
    {
        $this->releaseHandle();
    }

    /**
     * @return string|null
     */
    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    /**
     * @param string $encoding
     *
     * @return static
     */
    public function setEncoding(string $encoding): self
    {
        $this->encoding = $encoding;

        return $this;
    }

    /**
     * @param bool $useBom
     *
     * @return static
     */
    public function setUseBom(bool $useBom): self
    {
        $this->useBom = $useBom;

        return $this;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function prependBom(string $string): string
    {
        if ($this->encoding && ($bom = Bom::getEncodingBom($this->encoding))) {
            return $bom . $string;
        }

        return $string;
    }

    /**
     * release handle
     *
     * @return static
     */
    public function releaseHandle(): self
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
     * @return static
     */
    protected function initHandle($input, string $mode): self
    {
        if (is_resource($input)) {
            $this->handle = $input;
        } elseif (is_file($input)) {
            $this->handle = fopen($input, $mode) ?: null;
            if (!$this->handle) {
                throw new YaEtlException('Handle could not be opened in mode:' . $mode);
            }
        } else {
            throw new YaEtlException('$input is either not a resource or not a file');
        }

        return $this;
    }
}
