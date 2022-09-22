<?php

declare(strict_types=1);

namespace App\Model;

class Tool
{
    public string $slug;
    public string $title;
    public string $description;
    public string $url;
    public \DateTimeImmutable $date;
    public \DateTimeImmutable $lastModified;
    public int $priority;

    public function getSiteName(): string
    {
        return trim(preg_replace('/^https?:\/\/(.+)$/', '$1', $this->url) ?? '', '/');
    }
}
