<?php

namespace App\Provider;

use App\Model\Project;
use Content\Behaviour\ContentProviderInterface;

class ProjectProvider implements ContentProviderInterface
{
    public function getDirectory(): string
    {
        return 'project';
    }

    public function supports(string $className): bool
    {
        return is_a($className, Project::class, true);
    }
}
