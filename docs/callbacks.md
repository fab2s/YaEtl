# Callbacks (deprecated)

Although _deprecated_, Callbacks works just exactly as before, but you should consider using the new [Event handling implementation](events.md) for future work.

YaEtl inherits a KISS `CallbackInterface` from NodalFlow which can be used to trigger callback methods in various steps of the process.

- the `start($flow)` method is triggered when the Flow starts
- the `progress($flow, $node)` method is triggered each `$progressMod` time a full Flow iterates, which may occur whenever a `Traversable` node iterates.
- the `success($flow)` method is triggered when the Flow completes successfully
- the `fail($flow)` method is triggered when an exception was raised during the flow's execution. The exception is caught to perform few operations and re-thrown as is.

Each of these trigger slots takes current flow as first argument which allows control of the carrying flow. Please note that the flow provided may be a branch in some upstream flow, not necessarily the root flow. `progress($flow, $node)` additionally gets the current node as second argument which allows you to eventually get more insights about what is going on.
Please note that there is no guarantee that you will see each node in `progress()` as this method is only triggered each `$progressMod` time the flow iterates, and this can occur in any `Traversable` node.

YaEtl also extends two protected method from NodalFlow that will be triggered just before and after the flow's execution, `flowStrat()` and `flowEnd()`. You can override them to add more logic. These are not treated as events as they are always used by YaEtl and NodalFlow.

To use callbacks, just implement `CallbackInterface` and inject it in the flow.
```php
$flow = new YaEtl;
$callback = new ClassImplementingCallbackInterface;

$flow->setCallBack($callback);
```

A `CallbackAbstract` provides with a NoOp implementation of `CallbackInterface` in case you only need to override few of the interface methods without implementing the others.

A FlowStatus object is available within callbacks in case you need to make decisions based on how things went. The FlowStatus can indicate several states:

## Clean

That is if everything went well up to this point:
```php
$isClean = $flow->getFlowStatus()->isClean();
```

## Dirty

That is if the flow was broken (break interruption) by a node:
```php
$isDirty = $flow->getFlowStatus()->isDirty();
```

## Exception

That is if a node raised an exception during the execution:
```php
$isDirty = $flow->getFlowStatus()->isException();
```
