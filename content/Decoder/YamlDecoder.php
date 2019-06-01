<?php

namespace Content\Decoder;

use Content\Behaviour\ContentDecoderInterface;
use Symfony\Component\Serializer\Encoder\YamlEncoder;

/**
 * Parse YAML data
 */
class YamlDecoder extends YamlEncoder implements ContentDecoderInterface
{
}
