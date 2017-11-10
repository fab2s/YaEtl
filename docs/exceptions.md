# Exceptions

When an `Exception` is thrown during execution, NodalFlow catches it, perform few cleanup operations, including triggering the proper callback and then re-throws it as is if it's a `NodalFlowException` or else throws a `NodalFlowException` with the original exception set as previous. This means that NodalFlow will only throw `NodalFlowException` unless an exception is raised within a callback of yours.

YaEtl defines its own exceptions, `YaEtlException`, which extends `NodalFlowException`. This means that `YaEtlException` will also be re-thrown as is when caught by NodalFlow. You can thus distinguish between pure flow exceptions and YaEtl ones with ease. `YaEtlException` provides with the exact same ability to add context as `NodalFlowException` through [ContextException](https://github.com/fab2s/ContextException).

You can thus add context to exception when implementing nodes which will make it easy to later retrieve and log (MonoLog/sentry etc ...).
