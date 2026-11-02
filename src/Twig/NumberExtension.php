<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class NumberExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('compact', [$this, 'formatCompact']),
        ];
    }

    /**
     * 579 → "579", 1592 → "1.6k" (or "1,6k" in French), 40359 → "40k"
     */
    public function formatCompact(int $value): string
    {
        if ($value < 1000) {
            return (string) $value;
        }

        $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $value < 10000 ? 1 : 0);

        $text = $formatter->format($value / 1000);

        if ($text === false) {
            throw new \Exception('Could not format the given number.');
        }

        return $text . 'k';
    }
}
