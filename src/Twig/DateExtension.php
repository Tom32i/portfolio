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

    public function getFilters(): array
    {
        return [
            new TwigFilter('month', [$this, 'formatMonth']),
            new TwigFilter('day', [$this, 'formatDay']),
        ];
    }

    /**
     * @param string|\DateTimeInterface $value
     */
    public function formatMonth($value, string $format = 'long-with-year'): string
    {
        return $this->format($value, self::MONTH_FORMATS[$format]);
    }

    /**
     * @param string|\DateTimeInterface $value
     */
    public function formatDay($value, string $format = 'full'): string
    {
        return $this->format($value, self::DAY_FORMATS[$format]);
    }

    /**
     * @param string|\DateTimeInterface $value
     */
    private function format($value, string $format): string
    {
        $date = $value instanceof \DateTimeInterface ? $value : new \DateTimeImmutable($value);

        $formatter = \IntlDateFormatter::create(
            null,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            \IntlTimeZone::createTimeZone($date->getTimezone()->getName()),
            \IntlDateFormatter::GREGORIAN,
            $format
        );

        if ($formatter === false) {
            throw new \Exception('Could not create date formatter.');
        }

        $text = $formatter->format($date->getTimestamp());

        if ($text === false) {
            throw new \Exception('Could not format the given date.');
        }

        return $text;
    }
}
