<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace {
    class_alias(\fab2s\YaEtl\YaEtlException::class, \fab2s\NodalFlow\YaEtlException::class);
    class_alias(\fab2s\YaEtl\Events\ProgressBarSubscriber::class, \fab2s\YaEtl\Laravel\Callbacks\ProgressBarSubscriber::class);
}

namespace fab2s\NodalFlow {
    if (!class_exists(YaEtlException::class)) {
        /** @deprecated YaEtlException use fab2s\NodalFlow\YaEtlException instead (NS update) */
        class YaEtlException
        {
        }
    }
}

namespace fab2s\YaEtl\Laravel\Callbacks {
    if (!class_exists(ProgressBarSubscriber::class)) {
        /** @deprecated ProgressBarSubscriber use fab2s\YaEtl\Events\ProgressBarSubscriber instead (NS update) */
        class ProgressBarSubscriber
        {
        }
    }
}
