<?php

declare(strict_types=1);

namespace App\Model;

class Article
{
    public string $title;
    public string $slug;
    public string $description;
    public \DateTimeImmutable $date;
    public \DateTimeImmutable $lastModified;
    public string $content;
    public string $language = 'fr';
    public ?string $cover = null;
}
