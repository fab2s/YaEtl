<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Traits;

/**
 * Trait CsvHandlerTrait
 */
trait CsvHandlerTrait
{
    /**
     * @var array
     */
    protected $header;

    /**
     * @var string
     */
    protected $delimiter = ',';

    /**
     * @var string
     */
    protected $enclosure = '"';

    /**
     * use more widespread " as default escape char instead of default \
     *
     * @var string
     */
    protected $escape = '"';

    /**
     * @var bool
     */
    protected $useHeader = false;

    /**
     * @var bool
     */
    protected $useSep = false;

    /**
     * @return array|null
     */
    public function getHeader(): ?array
    {
        return $this->header;
    }

    /**
     * @param array $header
     *
     * @return static
     */
    public function setHeader(array $header): self
    {
        $this->header = $header;

        return  $this;
    }

    /**
     * @param bool $useHeader
     *
     * @return static
     */
    public function setUseHeader(bool $useHeader): self
    {
        $this->useHeader = $useHeader;

        return $this;
    }

    /**
     * @param bool $useSep
     *
     * @return static
     */
    public function setUseSep(bool $useSep): self
    {
        $this->useSep = $useSep;

        return $this;
    }
}
