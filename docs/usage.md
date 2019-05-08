# Usage

## A fluent grammar

YaEtl can build complex and repeatable workflow fluently:

### from()

The `from(ExtractorInterface $extractor, ExtractorInterface $aggregateWith = null)` method adds an extractor as a source of records to the flow, which may or may not be aggregated with another one

An [Extractor](usage.md#extractor) is a Traversable Node that will be iterated upon each of his extracted records in the flow. Each records will then pass through all the remaining nodes, which could just be a transformer and a Loader to achieve a simple ETL workflow.

The second argument is there to address cases where records are split (sharded) among several sources. Aggregating Extractors makes it possible to extract collections across several sharded repositories within the same E(JQT)L operation. For example, if you have sharded records by date, you could instantiate several time the same dedicated extractor with relevant parameters, for each instance to extract from one specific date range and source in the same order and then use each of them as an aggregated Extractor in the workflow.

Each Extractor would then consume all its records before the next Extractor takes place, allowing you to ETL a large collection of ordered records coming from various physical sources as if you where doing it with a single extractor instance.

If you where to add an Extractor without aggregating it to another, it would then just generate its records, using, or not, each upstream record as argument. This would result into this extractor to generate several records each time it is triggered in the flow, eg, each time a records arrives at its point of execution in the flow.

### join()

The `join(JoinableInterface $extractor, JoinableInterface $joinFrom, OnClauseInterface $onClause)` methods adds an extractor that will perform a join operation upon another extractor's records

Join operation is pretty similar to a JOIN with a DBMS. Joiner can be used to enrich records and can either "LEFT" join by providing with a default enrichment, when they would not find matching records, or, just a regular join by triggering a "continue" type interruption which will make the flow skip the record and continue with the eventual next record form the first upstream extractor.

The nature of the join is defined by the `$onClause` argument which implements `OnClauseInterface`:

```php
$joinOnClause  = new OnClause('fromKeyAliasAsInRecord', 'joinKeyAliasAsInRecord', function ($upstreamRecord, $record) {
    return array_replace($record, $upstreamRecord);
});

$leftJoinOnClause  = new OnClause('fromKeyAliasAsInRecord', 'joinKeyAliasAsInRecord', [$suitableObject, 'suitableMethod], $defaultRecord);
```

### transform()

The `transform(TransformerInterface $trasformer)` method adds a Transformer to the flow that will transform each record one by one

[Transformers](usage.md#transformer) are simple really, they just take a record as parameter and return a transformed version of the record. Simplest use case could be to change character encoding, but they could also be used to match a loader data structure, as a way to make it reusable, or just because it is required by the business logic.

### branch()

The `branch(YaTl $yaEtlWorkflow)` method adds an entire flow in the flow, that will be treated as a single node in its carrier

Branches currently cannot be traversable. It's something that may be implemented at some point though, as it is technically feasible and even could be of some use. As any nodes, branch node accepts one argument and can, or not, pass a value to be used as parameter to the next node.

### qualify()

The `qualify(QualifierInterface $qualifier)` method adds a Qualifier to the flow that will qualify each record one by one and decide if and how the downstream Nodes shall proceed with it.
                                             
[Qualifiers](usage.md#qualifiers) are simple really, they just take a record as parameter and decide what the Flow shall do with it by returning :

- `true` to accept the record, eg let the Flow proceed untouched
- `false|null|void` to deny the record, eg trigger a continue on the carrier Flow (not ancestors)
- `InterrupterInterface` to leverage the full power of NodalFlow's [Interruptions](https://github.com/fab2s/NodalFlow/blob/master/docs/interruptions.md).

### to()

The `to(LoaderInterface $loader)` method adds a loader in the flow

[Loaders](usage.md#loader) are at the end of the line, but they are not necessarily at the end of the flow. They can return a value that would be used as argument to the eventual next node or else the first upstream Extractor. But YaEtl's Loaders are currently not returning values by default.

You can for example add a Loader at some point in the flow because the required record state is reached, but still continue with more transformations and data enrichment on the same input record for another set of Loaders in the same flow, which goes down to sharing the extraction among several related tasks.

This only could be a decent, organized and repeatable optimization if you where to often extract data from a relatively slow REST API that is needed by many different services in your infrastructure, with specific APIs and so on.

Again, each piece you build is reusable, the extractor written to get a list of records from a db to dump documents can be reused "as is" to push the same records into a remote REST API.


```php
/**
 * @param array $unsetList array of key to unset
 *
 * @throws NodalFlowException
 */
// public function __construct(array $unsetList)
$transformer = new KeyUnsetTransformer(['whatever' => 'keyToUnset1', 'KeyToUnset2']);
```

## In practice

```php
use fab2s\YaEtl\YaEtl;
use fab2s\YaEtl\Extractor\OnClose;

$yaEtl = new YaEtl;
$rootExtractor = new Extractor;

// Join option are defined using an OnClose object
$joinOnClose = new OnClose('fromKeyAliasAsInRecord', 'joinKeyAliasAsInRecord', function($upstreamRecord, $record) {
    return array_replace($upstreamRecord, $record);
});

// or if we want a left join instead
$leftJoinOnClose new OnClose('fromKeyAliasAsInRecord', 'joinKeyAliasAsInRecord', $aSuitableCallable, [
    // this would be our default record
    'joined_field1' => null,
    'joined_field2' => null,
    // ...
    // 'joined_fieldN' => defaultValue,
]);
    
$joinableExtractor = new JoinableExtractor;
$yaEtl->from($rootExtractor)
    // will join records from $joinableExtractor on $rootExtractor's ones
    // meaning the flow will continue (skip record) when no match are found
    ->join($joinableExtractor, $rootExtractor, $joinOnClose)
    // Will left join records from AnotherJoinableExtractor on $joinableExtractor ones
    // meaning the default will be in our case be merged with the incoming record
    // when AnotherJoinableExtractor does not have matching records
    ->join(new AnotherJoinableExtractor, $joinableExtractor, $leftJoinOnClose)
    // ...
    ->transform(new Transformer)
    ->transform(new AnotherTransformer)
    // ...
    ->to(new Loader)
    ->to(new AnotherLoader);

// and why not some qualified branches to do something specialy
// with a subset of the extract filtered by a qualifier
// keep this one for later
$preparePremiumPerkTransformer = new PreparePremiumPerkTransformer;
$qualifiedBranch = (new YaEtl)->qualify(new PremiumUserQualifier)
    ->transform($preparePremiumPerkTransformer)
    ->to(new PerkSenderLoader);
    
$yaEtl->branch($qualifiedBranch)
    // optionally set callbacks
    ->setCallBack(new ClassImplementingCallbackInterface)
    // run ETL
    ->exec();
    
// send some parameter directly to $qualifiedBranch's Tranformer 
// without passing through the Qualifier
$result = $qualifiedBranch->sendTo($preparePremiumPerkTransformer->getId(), $record);

// displays some basic stats
$stats = $yaEtl->getStats();
echo $stats['report'];
```

But YaEtl also allows things like :

```php
// one can even transform before extracting
// could be useful in case your extractor
// does something with $param and you need
// to properly format $param before Flow starts
(new YaEtl)->transform(new Transformer)
    ->from(new Extractor)
    ->transform(new AnotherTransformer)
    ->to(new Loader)
    ->exec($param);
```

or:

```php
// if you just need to use a transformer alone
$result = (new YaEtl)->transform(new Transformer)->exec($param);
// equivalent to
$result = (new Transformer)->exec($param)
```

or :

```php
// if you need to re-use loader standalone
(new YaEtl)->to(new Loader)->exec($param);
// equivalent to :
(new Loader)->exec($param)->flush();
```

and:

```php
// to use an extractor anywhere
foreach ((new Extractor)->getTraversable($param) as $record) {
    // do something with $record
}
```

The later does not yet have a strict flow equivalent as Flows and Branches do not yet support traverse-ability.

As every Node gets injected with the carrier flow, you can extend YaEtl to implement whatever context logic you could need to share among all nodes.
