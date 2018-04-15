<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Extractors\File;

/**
 * Class LineExtractor
 */
class LineExtractor extends FileExtractorAbstract
{
    /**
     * @param mixed $param
     *
     * @return \Generator
     */
    public function getTraversable($param = null)
    {
        if (!$this->extract($param)) {
            return;
        }

        if (false !== ($line = $this->getNextNonEmptyLine(true))) {
            yield $line;
        }

        while (false !== ($line = fgets($this->handle))) {
            if ('' === ($line = trim($line))) {
                continue;
            }

            yield $line;
        }

        $this->releaseHandle();
    }
}
