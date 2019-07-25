<?php

/*
 * This file is part of YaEtl.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace {
    /* @see  https://github.com/fab2s/YaEtl/issues/2 */
    class_alias(\fab2s\YaEtl\Extractors\File\CsvExtractor::class, \fab2s\YaEtl\Loaders\File\CsvExtractor::class);
}

namespace fab2s\YaEtl\Loaders\File {
    if (!class_exists(CsvExtractor::class)) {
        /** @deprecated CsvExtractor this is intended to help IDEs */
        class CsvExtractor
        {
        }
    }
}
