<?php

declare(strict_types=1);

namespace App\Model;

class Tool
{
    public string $slug;
    public string $title;
    public string $description;
    public string $url;
    public \DateTimeImmutable $lastModified;
    public int $stars;
    public int $priority;
}
