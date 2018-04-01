# FlowEvents

YaEtl extends events implemented by NodalFlow through [`symfony/event-dispatcher`](https://symfony.com/doc/current/components/event_dispatcher.html). You can easily register any existing dispatcher implementing Symfony's `EventDispatcherInterface`. 

YaEtl events are compatible and tested with [symfony/event-dispatcher](https://symfony.com/doc/current/components/event_dispatcher.html) versions `2.8.*`, `3.4.*` and `4.0.*` (php > 7.1). 

YaEtl provides each `dispatch()` call with a `YaEtlEvent` instance, extending NodalFlow's `FlowEvent` which extends Symfony `Event` and implement `FlowEventInterface`. Each YaEtlEvent instance carries the dispatcher's Flow instance, and eventually a Node instance, when the event is tied to a specific Node.

Have a look at [NodalFlow event documentation](https://github.com/fab2s/NodalFlow/docs/events.md) for more details.

## Usage

In order to make it simple to use any kind of `EventDispatcherInterface` implementation, YaEtl does not instantiate the default Symfony implementation until you actually call `$flow->getDispatcher()` or register a Callback (the old way). 

Flow event are stored as constants in `YaEtlEvent`, but since it extends `FlowEvent`, follows the same naming convention (flow.name) and late static binding is used, you can refer to `FlowEvent` events either by `FlowEvent::FLOW_NAME` or `YaEtlEvent::FLOW_NAME`, which means that NodalFlow event subscribers and handler will work with YaEtl.
Only the events added by YaEtl can only be mentioned as `YaEtlEvent::FLOW_NAME`.

You can set your own dispatcher before you use it to register YaEtl events (or just set it already setup) :

```php
$flow->setDispatcher(new CustomDispatcher);
```

or : 

```php
 $flow->setDispatcher($alreadySetupDispatcher);
```
  
But you can also just let YaEtl handle instantiation :

```php
$flow->getDispatcher()->addListener('flow.event.name', function(FlowEventInterface $event) {
    // always set 
    $flow = $event->getFlow();
    // not always set
    $node = $event->getNode();
    
    // do stuff ...
});
```

or even : 

```php
$flow->getDispatcher()->addSubscriber(new EventSubscriberInterfaceImplementation());
```

It is **important** to note that _each_ Flow instance carries _its own_ dispatcher instance. In most cases, it is ok to just register events on the Root Flow as it is the one controlling the executions of all its eventual children. You still get the big picture with Root Flow events, such as start, end and exceptions, but you do not have access to children iteration events (the `YaEtlEvent::FLOW_PROGRESS` event).
If you need more granularity, you will need to register events in each Flow you want to observe.

## `YaEtlEvent::FLOW_START`

Triggered when the Flow starts, the event only carries the flow instance.

```php
$flow->getDispatcher()->addListener(FlowEvent::FLOW_START, function(FlowEventInterface $event) {
    $flow = $event->getFlow();
    // do stuff ...
});
```

## `YaEtlEvent::FLOW_PROGRESS`

Triggered when node iterates in the Flow, the event carries the flow and the iterating node instances. 

```php
$flow->getDispatcher()->addListener(FlowEvent::FLOW_PROGRESS, function(FlowEventInterface $event) {
    $flow = $event->getFlow();
    $node = $event->getNode();
    // do stuff ...
});
```

As this is the most called event, a modulo is implemented to only fire it once every `$progressMod` iteration, plus one at the first record. The default is 1024, you can set it directly on the Flow :

```php
// increase to 100k
$flow->setProgressMod(100000);
// or for full granularity
$flow->setProgressMod(1);
```

Since the `$progressMod` modulo is applied to each iterating node iteration count, each iterating node will have an opportunity to fire the event. 
For example, using a `$progressMod` of 10 and extracting 10 categories chained to another extractor extracting items in these categories, the `FlowEvent::FLOW_PROGRESS` event will be fired once with the first extractor and each 10 items found in each categories from the second extractor.

## `YaEtlEvent::FLOW_CONTINUE`

Triggered when a node triggers a `continue` on the Flow, the event carries the flow and the node instance triggering the `continue`. 

```php
$flow->getDispatcher()->addListener(FlowEvent::FLOW_CONTINUE, function(FlowEventInterface $event) {
    $flow = $event->getFlow();
    $node = $event->getNode();
    // do stuff ...
});
```

## `YaEtlEvent::FLOW_BREAK`

Triggered when a node triggers a `break` on the Flow, the event carries the flow and the node instance triggering the `break`. 

```php
$flow->getDispatcher()->addListener(FlowEvent::FLOW_BREAK, function(FlowEventInterface $event) {
    $flow = $event->getFlow();
    $node = $event->getNode();
    // do stuff ...
});
```

## `YaEtlEvent::FLOW_FLUSH`

Triggered when a node triggers a `break` on the Flow, the event carries the flow and the node instance being flushed. 
This event is purely YaEtl.

```php
$flow->getDispatcher()->addListener(FlowEvent::FLOW_BREAK, function(FlowEventInterface $event) {
    $flow = $event->getFlow();
    $node = $event->getNode();
    // do stuff ...
});
```

## `YaEtlEvent::FLOW_SUCCESS`

Triggered when the Flow completes successfully (eg with no exceptions), the event only carries the flow instance.

```php
$flow->getDispatcher()->addListener(FlowEvent::FLOW_SUCCESS, function(FlowEventInterface $event) {
    $flow = $event->getFlow();
    // do stuff ...
});
```

## `YaEtlEvent::FLOW_FAIL`

Triggered when an exception is raised during Flow execution, the event carries the flow instance, and the node current Node instance in the Flow when the exception was thrown.

```php
$flow->getDispatcher()->addListener(FlowEvent::FLOW_FAIL, function(FlowEventInterface $event) {
    $flow = $event->getFlow();
    $node = $event->getNode();
    // if you need to inspect the exeption 
    $exception = $flow->getFlowStatus()->getException();
    // do stuff ...
});
```

The original exception is [re-thrown by NodalFlow](exceptions.md) after the execution of FlowEvent::FLOW_FAIL events.

## Compatibility

The event implementation is fully compatible with the old [Callback strategy](callbacks.md). Although deprecated, you do not have to do anything to continue using your `CallbackInterface` implementations.

Under the hood, a `CallbackWrapper` implementation of Symfony `EventSubscriberInterface` is used as a proxy to the `CallbackInterface` implementation.
