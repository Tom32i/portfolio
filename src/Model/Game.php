<?php

declare(strict_types=1);

namespace App\Model;

class Game
{
    public string $slug;
    public string $title;
    /** @var array<string, string> Description by locale */
    public array $description;
    public string $url;
    public \DateTimeImmutable $date;
    public \DateTimeImmutable $lastModified;
    public string $image;
    public ?string $video = null;
    public bool $featured = false;
    public int $priority;

    public function getDescription(string $locale): string
    {
        return $this->description[$locale] ?? $this->description['fr'] ?? (string) reset($this->description);
    }
}
