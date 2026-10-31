<?php

declare(strict_types=1);

namespace App\Model;

/**
 * An event I gave a talk at.
 */
class Event
{
    public string $slug;
    public string $title;
    public string $url;
    public int $priority;
}
