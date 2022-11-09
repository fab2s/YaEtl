<?php

/*
 * This file is part of YaEtl
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/YaEtl
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\YaEtl\Transformers\Arrays;

use DateTimeImmutable;
use DateTimeZone;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\YaEtl\Transformers\TransformerAbstract;

class DateFormatTransformer extends TransformerAbstract
{
    const DEFAULT_TIMEZONE = 'UTC';

    /**
     * @var array<string,array<string, string|null>>
     *                                               'key_name' => 'date_format' // Y-m-d => DateTimeImmutable instance
     *                                               or:
     *                                               'key_name' => ['from' => 'date_format', 'to' => 'date_format|null'] // to defaults to from
     */
    protected $setUp = [];

    /**
     * @var DateTimeZone
     */
    protected $dateTimeZoneFrom;

    /**
     * @var DateTimeZone
     */
    protected $dateTimeZoneTo;

    /**
     * @var array<string,string|array<string,string>>
     *                                                'key_name' => 'date_format' // Y-m-d => DateTimeImmutable instance
     *                                                or:
     *                                                'key_name' => ['from' => 'date_format', 'to' => 'date_format'] // to defaults to from
     *
     * @param DateTimeZone|null $dateTimeZoneFrom
     * @param DateTimeZone|null $dateTimeZoneTo
     *
     * @throws NodalFlowException
     */
    public function __construct(array $setup, DateTimeZone $dateTimeZoneFrom = null, DateTimeZone $dateTimeZoneTo = null)
    {
        parent::__construct();

        $this->initSetup($setup);
        $this->dateTimeZoneFrom = $dateTimeZoneFrom ?: new DateTimeZone(static::DEFAULT_TIMEZONE);
        $this->dateTimeZoneTo   = $dateTimeZoneTo ?: new DateTimeZone(static::DEFAULT_TIMEZONE);
    }

    public function exec($param = null)
    {
        foreach ($this->setUp as $key => $dateFormat) {
            $param[$key] = DateTimeImmutable::createFromFormat($dateFormat['from'], $param[$key], $this->dateTimeZoneFrom)
                ->setTimezone($this->dateTimeZoneTo);

            if ($dateFormat['to']) {
                $param[$key] = $param[$key]->format($dateFormat['to']);
            }
        }

        return $param;
    }

    /**
     * @param array<string,string|array<string,string>> $setup
     *
     * @return $this
     */
    protected function initSetup(array $setup): self
    {
        foreach ($setup as $key => $dateFormat) {
            $formatTo = null;
            if (is_array($dateFormat)) {
                $formatFrom = $dateFormat['from'];
                $formatTo   = $dateFormat['to'] ?? $formatFrom;
            } else {
                $formatFrom = $dateFormat;
            }

            $this->setUp[$key] = [
                'from' => $formatFrom,
                'to'   => $formatTo,
            ];
        }

        return $this;
    }
}
