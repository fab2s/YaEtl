<?php

/*
 * This file is part of YaEtl.
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
    protected $useHeader = true;

    /**
     * @var bool
     */
    protected $useSep = false;

    /**
     * @return array|null
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param bool $useHeader
     *
     * @return $this
     */
    public function setUseHeader($useHeader)
    {
        $this->useHeader = (bool) $useHeader;

        return $this;
    }

    /**
     * @param bool $useSep
     *
     * @return $this
     */
    public function setUseSep($useSep)
    {
        $this->useSep = (bool) $useSep;

        return $this;
    }

    /**
     * @param string|null $delimiter
     * @param string|null $enclosure
     * @param string|null $escape
     *
     * @return $this
     */
    protected function initCsvOptions($delimiter = null, $enclosure = null, $escape = null)
    {
        if ($delimiter !== null) {
            $this->delimiter = $delimiter;
        }

        if ($enclosure !== null) {
            $this->enclosure = $enclosure;
        }

        if ($escape !== null) {
            $this->escape = $escape;
        }

        return $this;
    }
}
