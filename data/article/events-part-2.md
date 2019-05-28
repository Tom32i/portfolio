---
date: "2016-01-16 10:00:02"
tags: ["Symfony", "Doctrine", "Lifecycle", "Event"]
title: "Doctrine Events as a source of information - Part II"
description: "Using data from Doctrine Lifecycle events to dispatch custom events in the Symfony event system."
---

_Not familiar with Symfony Events? Check out [the basics](../events-part-1)._

While defining your domain events, you may have noticed that events often reflect a change in the data.

The action of a user, creating, updating and deleting content in your app will consist in an event: a new user has registered, an order status has changed, etc.

In the context of Symfony, you are likely to rely on Doctrine Events to watch for these changes. Doctrine provides a single entry point for watching changes on the model.

> How can we combine Doctrine with our existing Event workflow?

## Doctrine events

Indeed Doctrine provides a convenient way to watch for events occurring on the data.

I'm talking about the [LifeCycle Events](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html#lifecycle-events) and the associated [Listeners and Subscribers](http://symfony.com/doc/current/cookbook/doctrine/event_listeners_subscribers.html).

The classic way to use Doctrine Events, as described in the Symfony documentation: listen for Doctrine events and then "do something with the entity", right there, in the listener.

There are a few problems with this approach:

1. Actions and consequences are coupled again.
2. We rely on two different event systems.
3. Doctrine events are too tangled with persistence concerns.

For all these reasons, I recommend that you only use Doctrine events as a __source of information__ and rely on Symfony Events to link your domain actions and consequences.

So here's how I suggest to extract information from doctrine events:

## Create your domain events

Let's create 3 generic events that reflects changes on the data:

- Created
- Updated
- Deleted

### Naming events

Let's define an event for the three basic operations on data:

```php
<?php

namespace EventBundle;

/**
 * Model event directory
 */
class ModelEvents
{
    /**
     * A new model has been created
     */
    const CREATED = 'created';

    /**
     * An existing model has been changed
     */
    const UPDATED = 'updated';

    /**
     * An existing model has been deleted
     */
    const DELETED = 'deleted';
}
```

### The event class

Now we create a class to embody these three events:

``` php
<?php

namespace EventBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Model event
 */
class ModelEvent extends Event
{
    /**
     * Model
     *
     * @var mixed
     */
    protected $model;

    /**
     * Constructor
     *
     * @param mixed $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getModel()
    {
        return $this->model;
    }
}
```

## Aggregating Doctrine Events

To catch Doctrine events, we're gonna create a Subscriber. The role of this subscriber is to produce Domain events with data from Doctrine events and feed them to a Symfony dispatcher:

```php
<?php

namespace EventBundle\Event\Subscriber;

use EventBundle\Event\ModelEvent;
use EventBundle\ModelEvents;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Doctrine subscriber
 */
class DoctrineSubscriber implements EventSubscriber
{
    /**
     *  Event Dispatcher
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Constructor
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'postUpdate',
            'postRemove',
        ];
    }

    /**
     * Post persist event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $event = new ModelEvent($args->getEntity());

        $this->dispatcher->dispatch(ModelEvents::CREATED, $event);
    }

    /**
     * Post update event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $event = new ModelEvent($args->getEntity());

        $this->dispatcher->dispatch(ModelEvents::UPDATED, $event);
    }

    /**
     * Post remove event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $event = new ModelEvent($args->getEntity());

        $this->dispatcher->dispatch(ModelEvents::DELETED, $event);
    }
}
```

Declare the Doctrine subscriber:

```yaml
services:
    # Doctrine Event Subscriber
    doctrine_event_subscriber:
        class: "EventBundle\Event\Subscriber\DoctrineSubscriber"
        arguments:
            - "@event_dispatcher"
        tags:
            - { name: "doctrine.event_subscriber", connection: "default" }
```

And voila! We just used Doctrine to produce simple domain events and dispatch them through the Symfony event system.

### Tracking changes

When data is updated, it's often relevent to track the list of changed attributes.

Let's create a new event class to carry this new piece of information:

``` php
<?php

namespace Acme\EventBundle\Event;

/**
 * Model event with changes
 */
class ModelChangedEvent extends ModelEvent
{
    /**
     * Changes made to the model
     *
     * @var array
     */
    private $changes;

    /**
     * Constructor
     *
     * @param mixed $model
     * @param array $changes
     */
    public function __construct($model, array $changes = [])
    {
        parent::__construct($model);

        $this->changes = $changes;
    }

    /**
     * Get changes
     *
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Has the given field changed?
     *
     * @param string $field
     *
     * @return boolean
     */
    public function hasChanged($field)
    {
        return isset($this->changes[$field]);
    }
}
```

When retrieving this list of changes from Doctrine, we encounter a small problem:

- The `preUpdate` event provides the list of changes in the entity, but is fired __before__ database operation. So you can't be sure that the persistence went through yet.
- The `postUpdate` assures you that persistence is done but does not hold the list of changes.

Here's the trick, let's complete our Doctrine subscriber:

```php
<?php

// ...
use Doctrine\ORM\Event\PreUpdateEventArgs;
use EventBundle\Event\ModelChangedEvent;
use EventBundle\Utils\Inventory;

class DoctrineSubscriber implements EventSubscriber
{
    // ...

    /**
     *  Inventory
     *
     * @var Invetory
     */
    private $inventory;

    //...
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        // ...
        $this->inventory = new Inventory();
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            // ...
            'preUpdate',
        ];
    }

    /**
     * Store change set for the entity
     *
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->inventory->setChangeSet($args->getEntity(), $args->getEntityChangeSet());
    }

    /**
     * Retrieve change set and dispatch an Updated event
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity  = $args->getEntity();
        $changes = $this->inventory->getChangeSet($entity);
        $event   = new ModelChangedEvent($entity, $changes);

        $this->dispatcher->dispatch(ModelEvents::UPDATED, $event);
    }
```

How do we store and retrieve the ChangeSet you might ask me?

Here's a [simple implementation of the Inventory](https://gist.github.com/Tom32i/54876b5236d477a31126) that provides `setChangeSet` and `getChangeSet` methods.

### Deleted entities

Some time ago, I needed to watch for deleted entities in my app.

I naturally used `postRemove` event, but when I tried to get the identifier of my entity with the `getId` method: the result was `null`.

Indeed Doctrine cleans any identifying attribute in your entity after it removed it.

It's convenient because you can't re-persist the entity accidentally, but I _needed_ to identify deleted entities in my app!

Fortunately, in the `preRemove` event, the identifiers are available.

So we can do just what we did with the change set: store the id for the given entity on `preRemove` and retrieve it on `postFlush`.

Let's extends the ModelEvent again to support these identifiers:

```php
<?php

namespace EventBundle\Event;

/**
 * Model event with identifiers
 */
class ModelDeletedEvent extends ModelEvent
{
    /**
     * Identifiers of the model
     *
     * @var array
     */
    private $identifiers;

    /**
     * Constructor
     *
     * @param mixed $model
     * @param array $identifiers
     */
    public function __construct($model, array $identifiers = [])
    {
        parent::__construct($model);

        $this->identifiers = $identifiers;
    }

    /**
     * Get identifiers
     *
     * @return array
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }
}
```

Now we complete our listener:

```php
<?php

// ...

use EventBundle\Event\ModelDeletedEvent;

class DoctrineSubscriber implements EventSubscriber
{
    // ...

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            // ...
            'preRemove',
        ];
    }

    /**
     * Pre remove event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity        = $args->getEntity();
        $classMetadata = $args->getEntityManager()->getClassMetadata(get_class($entity));
        $identifiers   = $classMetadata->getIdentifierValues($entity);

        $this->inventory->setIdentifiers($entity, $identifiers);
    }

    /**
     * Post remove event handler
     *
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $entity      = $args->getEntity();
        $identifiers = $this->inventory->getIdentifiers($entity);
        $event       = new ModelDeletedEvent($entity, $identifiers);

        $this->dispatcher->dispatch(ModelEvents::DELETED, $event);
    }
```

# Are we there yet?

No quite, but almost...

We have a __decoupled workflow__: domain-related events that link our actions and consequences.

We have __consistancy__: everytime a change occures on the model, regardless of what caused it, the corresponding domain event is fired.

There's still an issue we need to adress: [it's about Response time optimisation](../events-part-3).
