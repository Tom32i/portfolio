<?php

namespace App\Serializer;

use App\Model\Project;
use Content\Behaviour\ContentDenormalizerInterface;

class ProjectDenormalizer implements ContentDenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return new Project(
            $this->get($data, 'title'),
            $this->get($data, 'description'),
            $this->get($data, 'url'),
            $this->get($data, 'date'),
            $this->get($data, 'image')
        );
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_a($type, Project::class, true);
    }

    private function get(array $data, string $property, $default = null)
    {
        return isset($data[$property]) ? $data[$property] : $default;
    }
}
