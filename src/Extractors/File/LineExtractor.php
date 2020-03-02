<?php

/*
 * This file is part of YaEtl
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
    public function getTraversable($param = null): iterable
    {
        while ($this->extract($param)) {
            if (!$this->readBom()) {
                return;
            }

            while (null !== ($line = $this->getNextNonEmptyLine())) {
                yield $line;
            }
        }

        $this->releaseHandle();
    }
}
