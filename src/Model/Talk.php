<?php

declare(strict_types=1);

namespace App\Model;

class Talk
{
    public string $title;
    public string $description;
    /** @var array<string, string> Short description by locale */
    public array $summary = [];
    public string $slug;
    public int $duration;
    public \DateTimeImmutable $date;
    public \DateTimeImmutable $lastModified;
    public string $slides;
    public string $sources;
    public ?string $video = null;
    public ?string $image = null;
    /** @var string[] */
    public array $events = [];

    public function getSummary(string $locale): string
    {
        return $this->summary[$locale] ?? $this->summary['fr'] ?? $this->description;
    }
}
