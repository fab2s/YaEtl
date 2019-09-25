<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Events;

use fab2s\NodalFlow\Events\FlowEvent;

/**
 * Class YaEtlEvent
 */
class YaEtlEvent extends FlowEvent
{
    /**
     * add flush Events
     */
    const FLOW_FLUSH = 'flow.flush';

    /**
     * @return array
     */
    public static function getEventList(): array
    {
        if (!isset(static::$eventList)) {
            static::$eventList = array_replace(parent::getEventList(), [
                static::FLOW_FLUSH => static::FLOW_FLUSH,
            ]);
        }

        return static::$eventList;
    }
}
