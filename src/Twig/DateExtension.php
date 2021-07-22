<?php

declare(strict_types=1);

/*
 * This file is part of the Tribü project.
 *
 * Copyright © Tribü
 *
 * @author Elao <contact@elao.com>
 */

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateExtension extends AbstractExtension
{
    public const MONTH_FORMATS = [
        'long-with-year' => 'MMMM Y',
        'short' => 'LLL',
    ];
    public const DAY_FORMATS = [
        'full' => 'd MMMM Y',
        'short' => 'd/MM/Y',
    ];

    public function getFilters()
    {
        return [
            new TwigFilter('month', [$this, 'formatMonth']),
            new TwigFilter('day', [$this, 'formatDay']),
        ];
    }

    public function formatMonth($value, string $format = 'long-with-year')
    {
        $date = $value instanceof \DateTimeInterface ? $value : new \DateTimeImmutable($value);

        $formatter = \IntlDateFormatter::create(
            null,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            \IntlTimeZone::createTimeZone($date->getTimezone()->getName()),
            \IntlDateFormatter::GREGORIAN,
            self::MONTH_FORMATS[$format]
        );

        return $formatter->format($date->getTimestamp());
    }

    public function formatDay($value, string $format = 'full')
    {
        $date = $value instanceof \DateTimeInterface ? $value : new \DateTimeImmutable($value);

        $formatter = \IntlDateFormatter::create(
            null,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            \IntlTimeZone::createTimeZone($date->getTimezone()->getName()),
            \IntlDateFormatter::GREGORIAN,
            self::DAY_FORMATS[$format]
        );

        return $formatter->format($date->getTimestamp());
    }
}
