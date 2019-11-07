<?php

namespace App\Serializer;

use App\Model\Talk;
use Content\Behaviour\ContentDenormalizerInterface;

class TalkDenormalizer implements ContentDenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return new Talk(
            $this->get($data, 'title'),
            $this->get($data, 'description'),
            $this->get($data, 'duration'),
            $this->get($data, 'date'),
            $this->get($data, 'slides'),
            $this->get($data, 'video')
        );
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_a($type, Talk::class, true);
    }

    private function get(array $data, string $property, $default = null)
    {
        return isset($data[$property]) ? $data[$property] : $default;
    }
}
