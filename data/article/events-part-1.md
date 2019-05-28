---
date: "2016-01-16 10:00:01"
tags: ["Symfony", "Event", "Dispatcher", "Pattern", "Custom"]
title: "Decoupling with Symfony Events - Part I"
description: "How Symfony events can help you build a better workflow for your application by separating actions and consequences."
---

_Already familiar with the Symfony Event Dispather? [Skip the basics](../events-part-2)_

It's monday and your client tells you:

> When a user places an order, the app should send a notification to the administrator.

The most straight forward way to implement that in Symfony is to go in the controller or service that _places the order_ (the action) and write code that _notifies the administrator_ (the consequence).

By structuring an application like that, the code handling orders would be very coupled to the one responsible for notifications.

Although these two concerns are _linked_, they should not be _coupled_.
This will make an application difficult to maintain and to evolve.

## Events to the rescue

Events are messages that link actions to consequences in your application while keeping them __independent__.

Dispatching an event is identifying a meaningful domain action.
The event provides an entry point for every consequence that reacts to this action.

Let's take our previous example and add one more feature:

> The app should also generate an invoice PDF file and save it on the server when an order is placed.

Given this highly coupled code:
![](/img/article/coupled.svg)

Refactoring the same feature with events would look like:

![](/img/article/decoupled.svg)

Now the action and its two consequences are independent, linked by an event.

### The benefices of separating concerns

__Team work:__
The developer working on the Notifier feature won't depend on the team that provides the orders workflow. They can work in parallel, or not, and avoid being slowed down by conflicts.

__Evolutivity:__
It's very easy to code a new _consequence_ without affecting the _action_.
By adding a listener, you can plug literally any process on your domain event: logging, exporting datas, building some cache, sending emails...

__Flexibilty:__
Consequences can be activated and deactivated by configuration or context very simply.

## Getting it done

Fortunately Symfony comes with a nice [Event Dispatcher component](http://symfony.com/doc/current/components/event_dispatcher/introduction.html).

The documentation is thorough and gives complete implementation examples, you should read it.

Once you're familiar with the tools, design your workflow by identifying your domain _actions_ and _consequences_ and naming your domain _events_.

Finally, proceed with the implementation:

### Create and dispatch domain events

Your domain events are messages meant to transport any relevant information about what happened. They should be emitted from your controllers and services where the action occurs.

The only requirement for an event is to be an instance of `Symfony\Component\EventDispatcher\Event`.

You can directly use this class by omitting the event object parameter:

```php
$dispatcher->dispatch('my_event');
```

But you may want to write your own classes, to structure your events and give them custom properties and methods. Just have your class extends the default `Symfony\Component\EventDispatcher\Event` class and dispatch an instance of your class:

```php
$dispatcher->dispatch('my_event', new MyDomainEvent());
```

__Good practice:__ Symfony recommends that you [reference all domain events in a static class](http://symfony.com/doc/current/components/event_dispatcher/introduction.html#the-static-events-class). It will avoid some typos ;)

__ProTip:__ Several events can use the same class, if their behavior is similar. Eg: you can dispatch `order.registered`, `order.shipped`,  `order.arrived` events using the same `OrderStatusChangedEvent` class.

### Setup your workflow

Now all you need is to connect _actions_ to _consequences_ using [`Listeners`](http://symfony.com/doc/current/components/event_dispatcher/introduction.html#connecting-listeners) (or [`Subscribers`](http://symfony.com/doc/current/components/event_dispatcher/introduction.html#using-event-subscribers)).

The cookbook for [subscribers and listeners](http://symfony.com/doc/current/cookbook/event_dispatcher/event_listener.html) will tell you everything you need to know.

# How about Doctrine events?

Doctrine comes with its own event system, [how do we deal with these?](../events-part-2)
