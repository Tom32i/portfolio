Portfolio / Blog
================

## Installation

    composer install

    npm instlal

## Usage

    bin/console server:run

http://localhost:8000

## Build

    bin/console content:build

## Content

### New content

- Create a source folder: `data/foo`
- Create a Foo class representinf the model: `src/Model/Foo.php`.
- Create a content denormalizer: `src/Serializer/FooDenormalizer` that implements `Content\Behaviour\ContentDenormalizerInterface`.
- Create a content provider: `src/Provider/FooProvider` that implements `Content\Behaviour\ContentProviderInterface`.
