---
date: "2015-09-15 10:00:00"
tags: ["Symfony", "Serialization"]
title: "Better serialization with Symfony"
description: "How to write strong and clean Serialization process with Symfony2 and why you should."
---

If you ever built an export script or an API, you surely had to format your content and deal with serialization.

In Symfony, I often see this matter handled by the [JMS Serializer](http://jmsyst.com/libs/serializer), as it is suggested by Symfony documentation.

But after using it in several projects, I'm not totally happy with it.
I've encountered small problems, mostly __assumptions that don't fit my needs__ and __can't be overridden or redefined easily__.
Which make them deal-breakers in my opinion.

The solution may be fine for big backends and API where you just want to have your entities serialized "automatically".

However, if you're working on specific domain logic for small/medium projects (as I mostly do), you might want to look for more flexible solutions.

But you know what?

## Symfony has a great serialization component!

Symfony already addressed the problem of content serialization with the __Serializer Component__.

It is not as _ready to use_ as JMS Serializer but it is __extendable__ and __flexible__.

_Quick reminder_: In the Symfony serialization component, a serializer is composed of two halves:

- The __Normalizer__: responsible for transforming the source object into an array (_normalize/denormalize_).
- The __Encoder__: responsible for transforming the normalised data into a formatted string (_encode/decode_).

You can provide the serializer with several normalizers and encoders so it can handle more serialization cases.

Before going further, I recommend that you refresh your memory with [the documentation](http://symfony.com/doc/current/components/serializer.html) if you're not familiar with this component.

### Your domain logic lies into the normalizer

The Serializer component is shipped with several encoders (notably _JSON_ and _XML_ encoders) but you could write quite easily an encoder for any format you need: _CSV_, _XML_,...

But the heart of the problem of serialization is to transform your object into array (a.k.a the normalization), that's what you do in JMS when you write annotations to tell which property should be included and how.

_That's where the value is, so that's where you want to put your time and efforts._

> Need to serialize a specific object in a specific way?
Declare a normalizer that supports this single model!

To write a custom normalizer, you need to implement `NormalizerInterface`, which describes two methods:

- __supportsNormalization__: Answers the question "Can you normalize that object?".
- __normalize__: Does the transformation from object into array.

Here's an example:

``` php
<?php

namespace Acme\Serializer\Normalizer;

use Acme\Model\User;
use Acme\Model\Group;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * User normalizer
 */
class UserNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            'id'     => $object->getId(),
            'name'   => $object->getName(),
            'groups' => array_map(
                function (Group $group) {
                    return $group->getId();
                },
                $object->getGroups()
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof User;
    }
}
```

_Note:_ You are free to add some logic/complexity here, you've separated the _model_ from the _serialization of the model_. Hurrah for decoupling \o/

The result of the normalization would be:

``` php
<?php
[
    'id'     => 1,
    'name'   => 'Foo Bar',
    'groups' => [1, 2]
]
```

### Handling object associations

When normalizing an object, you might encouter relations to other objects that the normalizer doesn't support. The `SerializerAwareNormalizer` is here to help you:

When your normalizer extends the `SerializerAwareNormalizer`, it will receive the parent serializer as a dependence. So you can entrust the normalization of an other object back to the serializer (which may have other normalizers for that object).

Let's update our previous example:

``` php
<?php

namespace Acme\Serializer\Normalizer;

// ...
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * User normalizer
 */
class UserNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            // ...
            'groups' => array_map(
                function ($object) use ($format, $context) {
                    return $this->serializer->normalize($object, $format, $context);
                },
                $object->getGroups()
            ),
        ];
    }
}
```

All you need to do now is to write a normalizer that supports `Group` objects!

``` php
<?php

// ...

/**
 * Group normalizer
 */
class GroupNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            'id'   => $object->getId(),
            'name' => $object->getName(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Group;
    }
}
```

The result of the normalization:

``` php
<?php
[
    'id'        => 1,
    'firstname' => 'Foo',
    'lastname'  => 'Bar',
    'groups'    => [
        [
            'id'   => 1,
            'name' => 'FooFighters'
        ],
        [
            'id'   => 2,
            'name' => 'BarFighters'
        ],
    ],
]
```

_Note:_ In the `supportsNormalization` method, you could very well say that you handle a specific `interface` instead of a single object. Making it a normalizer that handles all models that behave a certain way.

### The context

The Serializer Component offers a `$context` variable that is passed on throught the whole serialization process.

You can use it to store any information that your normalizer would need and affect their behavior.

``` php
<?php

namespace Acme\Serializer\Normalizer;

// ...
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * User normalizer
 */
class UserNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return [
            // ...
            'groups' => array_map(
                function ($object) use ($format, $context) {
                    if ($context['include_relations']) {
                        return $this->serializer->normalize($object, $format, $context);
                    } else {
                        return $object->getId();
                    }
                },
                $object->getGroups()
            ),
        ];
    }
}
```

Can you see how our serializer is getting flexible and powerfull?

Here's a few more custom normalizers I wrote for a REST API:

- __[Doctrine's Collection](https://gist.github.com/Tom32i/773de875f92322925bd3#file-collectionnormalizer-php)__: Similar to what we did with groups in the example above.
- __[DateTime](https://gist.github.com/Tom32i/773de875f92322925bd3#file-datetimenormalizer-php)__: The one class in my app responsible for formating Dates for the API.
- __[Form Error](https://gist.github.com/Tom32i/773de875f92322925bd3#file-formerrornormalizer-php)__: returns a simple array with field names as keys and error messages as values.

You might also want to extend the [`ObjectNormalizer`](https://github.com/symfony/Serializer/blob/4d03a053097b926694a878fcd4b3f230dca56717/Normalizer/ObjectNormalizer.php) shipped with the Symfony Serializer component. It loops over the properties of the object to serialize and forward all non-scalar values back to the serializer (which makes it works with your custom normalizers!). However it makes no assumption about how circular references should be treated, so it requires a little work. But that would be a topic for another article.

## The Serializer(s) as service(s)

Once you have all the normalizers you need, it can be useful to declare them as services.
Giving these normalizers access to __all the power of a service__, like injecting dependencies that you can ask for extra information when normalizing a model (database, webservice, file sytem, ...).

To do so, declare a service for each of the encoders you will need:

``` yaml
services:
    # JSON Encoder
    acme.encoder.json:
        class: 'Symfony\Component\Serializer\Encoder\JsonEncoder'

    # XML Encoder
    acme.encoder.xml:
        class: 'Symfony\Component\Serializer\Encoder\XmlEncoder'
```

Declare your custom normalizers as services:

``` yaml
services:
    # User Normalizer
    acme.normalizer.user:
        class: 'Acme\Serializer\Normalizer\UserNormalizer'

    # Group Normalizer
    acme.normalizer.group:
        class: 'Acme\Serializer\Normalizer\GroupNormalizer'
```

Finally, compose as many serializers as you need with different normalizers and encoders:

``` yaml
services:
    # Serializer
    acme.serializer.default:
        class: 'Symfony\Component\Serializer\Serializer'
        arguments:
            0:
                - '@acme.normalizer.user'
                - '@acme.normalizer.group'
                - '@serializer.normalizer.object'
            1:
                - '@acme.encoder.json'
                - '@acme.encoder.xml'
```

_Note:_ If you [enabled the serializer services](http://symfony.com/doc/current/cookbook/serializer.html), as I did here, you can use the `serializer.normalizer.object` service as a fallback normalizer for all objects that you didn't specifically handle with a custom normalizer.

## The benefits:

Structuring your serializer this way, you have a very modular and flexible system:

__Flexibility__: by creating your own normalizers, you don't have to conform to a structure imposed by a library. Just write the code that does what your project needs.

__Testability__: normalizers are testable PHP classes, you can unit test the result of normalization.

__Separation of concerns__: different parts of your application may need to serialize the same object in different ways. It's their role to know how, not the object itself: that means no more complicated sets of groups in the object annotations.

__Performance__: Symfony Serializer with custom normalizers performs _3 times faster_ than JMS Serializer in my experience.


