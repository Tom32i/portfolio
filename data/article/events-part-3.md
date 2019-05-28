---
date: "2016-01-16 10:00:03"
tags: ["Symfony", "Event", "Kernel", "Terminate", "Response"]
title: "Delaying listeners execution to improve response time - Part III"
description: "Improve your app response time by calling your listeners on kernel.terminate with the DelayedEventDispatcher."
---

Although we just set up a [decoupled](../events-part-1) and [consistant](../events-part-2) event workflow, our work is not done yet.

There's still a small problem with our code as it is:

## Events in Symfony are synchronous

That means when you dispatch an event, the code of the listener is executed right there, not later.

So if an event is fired during the Request process, any listener is also executed during the processing of the Request, before any Response can be sent to the client.

> If you have an event triggering a 1 second process in a 200ms request, your client will wait 1.2 seconds for the response.

Worst, the triggered process could fail and throw an exception, leaving your client with a error 500.

_In fact, in most cases, you don't need the result of the process to send the Response to the client._

## Delay the execution of your processes

You need the _consequences_ of domain actions to run __after__ the Response has been sent.

One convenient solution is to stack events in a queue instead of dispatching them directly. Wait for the Response to be sent then dispatch every event waiting in the queue.

### Stacking events in a queue

Let's create an Event Dispatcher that waits for the Kernel event _terminate_ to dispatch any event.

A simple way to do so is to embed the existing Symfony EventDispatcher in our own disptacher:

```php
<?php

namespace EventBundle\Event\Dispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Dispatch events on Kernel Terminate
 */
class DelayedEventDispatcher implements EventDispatcherInterface, EventSubscriberInterface
{
    /**
     *  Event Dispatcher
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Queued events
     *
     * @var array
     */
    private $queue;

    /**
     * Is the dispatcher ready to dispatch events?
     *
     * @var boolean
     */
    private $ready;

    /**
     * The Deleyad event dispatcher wraps another dispatcher
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->queue      = [];
        $this->ready      = false;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::TERMINATE => 'setReady'];
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($eventName, Event $event = null)
    {
        if (!$this->ready) {
            $this->queue[] = ['name' => $eventName, 'instance' => $event];

            return $event;
        }

        return $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * Set ready
     */
    public function setReady()
    {
        if (!$this->ready) {
            $this->ready = true;

            while ($event = array_shift($this->queue)) {
                $this->dispatcher->dispatch($event['name'], $event['instance']);
            }
        }
    }

    // Actualy, there are a few more methods to implement to respect the EventDispatcherInterface.
    // But they just forward logic to the embedded dispatcher.
}
```

Declare the delayed event dispatcher service:

```yaml
services:
    # Delayed Event Dispatcher
    delayed_event_dispatcher:
        class: "EventBundle\Event\Dispatcher\DelayedEventDispatcher"
        arguments:
            - @event_dispatcher
        tags:
            - { name: "kernel.event_subscriber" }

```

Now you just need to dispatch your domain events through this `DelayedDispatcher`!

Since this dispatcher only fires events in _kernel.terminate_, your listeners and subscribers will run after the client is served.

__Note:__ If any listener triggers another event during the _kernel.terminate_ phase, the new event will be dispatched instantly because the `DelayedDispatcher` is now in _ready_ state.

# Final words

I'm not saying that _you should do as I say_ because _it's good practice_.

This is feedback and advice from a few years of experimentation with events through several Symfony projects.

Not all of the apps I worked on implements every bit of what I exposed in theses articles. Or not in the exact same way. Or not at all! Not every app needs an event workflow ;)

That being said, I realy think the Symfony Event Dispatcher component, combined with Doctrine events and some tricks like the DelayedEventDispatcher, are great tools to handle common action/consequence needs.

And I hope you learned a cool way to address the problem you were struggling with lately ;)
