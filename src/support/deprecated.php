<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace {
    class_alias(\fab2s\YaEtl\YaEtlException::class, \fab2s\NodalFlow\YaEtlException::class);
}

namespace fab2s\NodalFlow {
    if (!class_exists(YaEtlException::class)) {
        /** @deprecated YaEtlException this is intended to help IDEs */
        class YaEtlException
        {
        }
    }
}
