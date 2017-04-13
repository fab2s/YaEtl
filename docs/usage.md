# Usage

## A fluent grammar
YaEtl can build complex and repeatable workflows fluently:
* the `from(ExtractorInterface $extractor, ExtractorInterface $aggregateWith = null)` method adds an extractor as a source of records to the flow, which may or may not be aggregated with another one and later referred as Fromer

    A fromer is a Traversable Node that will be iterated upon each of his extracted records in the flow. Each records will then pass through all the remaining nodes, which could just be a transformer and a Toer to achieve a simple ETL workflow.
    The second argument is there to address cases where records are split (sharded) among several sources. Aggregating fromers would then make it possible to extract collections across several sharded repositories within the same E(JT)L operation. For example, if you have sharded records by date, you could instantiate several time the same dedicated exactor with relevant parameters, for each instance to extract from one specific date range and source in the same order and then use each of them as an aggregated fromer in the workflow.
    Each extractor would then consume all its records before the next fromer takes place, allowing you to ETL a large collection of ordered records coming from various physical sources as if you where doing it with a single extractor instance.
    If you where to add a fromer without aggregating it to another, it would then just generate its records, using, or not, each upstream record as argument. This would result into this extractor to generate several records each time it is triggered in the flow, eg, each time a records arrives at its point of execution in the flow.
* the `join(JoinableInterface $extractor, JoinableInterface $joinFrom, OnClauseInterface $onClause)` methods adds an extractor that will perform a join operation upon another extractor's records, later referred as Joiners

    Join operation is pretty similar to a JOIN with a DBMS. Joiner can be used to enrich records and can either "LEFT" join by providing with a default enrichment, when they would not find matching records, or, just a regular join by triggering a "continue" type interruption which will make the flow skip the record and continue with the eventual next record form the first upstream extractor.
    The nature of the join is defined by the `$onClause` argument which implements `OnClauseInterface`:
    ```php
    $joinOnClause  = new OnClause('fromKeyAliasAsInRecord', 'fromKeyAliasAsInRecord', function ($upstreamRecord, $record) {
        return array_replace($record, $upstreamRecord);
    });

    $leftJoinOnClause  = new OnClause('fromKeyAliasAsInRecord', 'fromKeyAliasAsInRecord', [$suitableObject, 'suitableMethod], $defaultRecord);
    ```

* the `transform(TransformerInterface $trasformer)` method adds a transformer to the flow that will transform each record one by one, later referred as Transformer

    Transformers are simple really, they just take a record as parameter and return a transformed version of the record. Simplest use case could be to change character encoding, but they could also be used to match a loader data structure, as a way to make it reusable, or just because it is required by the business logic.
* the `branch(YaTl $yaEtlWorkflow)` method adds an entire flow in the flow, that will be treated as a single node and later referred as Brancher

    Branches currently cannot be traversable. It's something that may be implemented at some point though as it is technically feasible and even could be of some use. As any nodes, branch node accepts one argument and can, or not, pass a value to be used as parameter to the next node.
* the `to(LoaderInterface $loader)` method adds a loader in the flow, later referred as Toer

    Toers are at the end of the line, but they are not necessarily at the end of the flow. They can return a value that would be used as argument to the eventual next node or else the first upstream Extractor. But YaEtl's Loaders are currently not returning values.
    You can for example add toers at some point in the flow because the required record state is reached, but still continue with more transformations and data enrichment on the same input record for another set of toers in the same flow, which goes down to sharing the extraction among many related tasks.
    This only could be a decent, organized and repeatable optimization if you where to often extract data from a relatively slow REST API that is needed by many different services in your infrastructure, with themselves specific APIs and so on.

Again, each piece you build is reusable, the extractor written to get a list of records from a db to dump documents can be reused "as is" to push the same records into a remote REST API.

## Extractor

The extractor should usually fetch many records at once using its `extract()` method and should return them one by one through its `getTraversable()` method, inherited from Nodalflow's `TraversableInterface`.

The `extract()` is YaEtl specific and is used to distinguish the actual extract, which in most cases should fetch many records at once, and the pure flow act of providing with each records one by one. It must return true in case of success (eg we got records) and false in case we fetched all records already.

YaEtl iterate on what's returned by `getTraversable()` _while_ `extract()` returns true.

A KISS implementation could look like :

```php
use fab2s\YaEtl\ExtractorAbstract;

/**
 * Class Extractor
 */
class Extractor extends ExtractorAbstract
{
    /**
     * get records from somewhere
     * @return bool
     */
    public function extract()
    {
        if (!($this->extracted = $this->getAllRecords())) {
                return false;
        }

        return true;
    }

    /**
     * @return \Generator
     */
    public function getTraversable()
    {
        foreach ($this->extracted as $record) {
            yield $record;
        }
    }
}
```

Of course, it's not realistic in many cases to get all records at once, but it's is simple to paginate the result and then exhaust each page record by record, especially since the extract operation is separated and used somehow in the "meta" loop.

YaEtl comes with many partial to complete Extractor implementations to address many use case with some emphasis on databases in general and [PDO](http://php.net/PDO) in particular:
* `ExtractorAbstract` (implementing `ExtractorInterface` which extends NodalFlow's `TraversableInterface`) is the minimal NodalFlow setup you can extend to create an extractor
* `ExtractorLimitAbstract` (extending `ExtractorAbstract`) adds logic to handle extraction limits
* `ExtractorBatchLimitAbstract` (extending `ExtractorLimitAbstract`) adds logic to additionally handle batch extraction, eg paginated queries
* `DbExtractorAbstract` (extending `ExtractorBatchLimitAbstract`) adds logic to extract from any DBMS
* `PdoExtractor` (extending `DbExtractorAbstract`) is a ready to use PDO extractor implementation
* `UniqueKeyExtractorAbstract` (extending `DbExtractorAbstract` and implementing `JoinableIUnterface`) adds logic to extract and Join from any DBMS provided that the sql query fetches records against a single unique KEY. The unique key may be composite for extraction, but joining is currently only supported against a single unique key. While you can join records on a single unique keys from an extractor that actually query on some other (composite) keys (as long as the Joined key remains unique in the records), you cannot currently Join on a composite unique key directly.
* `PdoUniqueKeyExtractor` (extending `PdoExtractorAbstract` and implementing `JoinableIUnterface`) is a ready to use PDO unique key extractor.

Implementing a `Joinable` extractor requires a bit more work as there is a need to build records maps and transmit them among joiners. But it can be pretty quick using `UniqueKeyExtractorAbstract` and / or `PdoUniqueKeyExtractor` as they provides with much of the work. The Laravel `UniqueKeyExtractor` class is a working example of a class extending `PdoUniqueKeyExtractor`, being itself a working example of a class implementing `UniqueKeyExtractorAbstract`.

Both PDO extractors deactivate MysQl buffered query if needed to speed up fetching large amounts of records.

## Transformer

The Transformer is where records are transformed. Data from various sources provided by the extractor could be assembled into totally different data structures, ready to be handled by the next node.

A trivial example could be an extractor gathering user data from both user and user_address table and passing them to an extractor that would use the data to return a complete address string, ready to be pushed into some postal API by the loader.

```php
use fab2s\YaEtl\Transformers\TransformerAbstract;

/**
 * Class Transformer
 */
class Transformer extends TransformerAbstract
{
    /**
     * Inherited from NodalFlow's ExecNodeInterface
     * @return bool
     */
    public function exec($record)
    {
        // do whatever you need to obtain
        // the proper output ready for next operation
        return array_map('trim', $record);
    }
}
```

YaEtl also includes a ready to use `CallableTransformer` which takes a `callable` as argument to its constructor:
```php
use fab2s\YaEtl\Transformers\CallableTransformer;

$callableTransformer = new CallableTransformer(function($record) {
    return doSomething($record);
});

// or
$callableTransformer = new CallableTransformer([$someObject, 'relevantMethod']);
```

It goes without saying that the `callable` should be relevant with the task.

## Loader

The Loader holds the responsibility to "load" somehow and somewhere (or at least do something with) the record after it came through all upstream nodes in the flow. In many cases it is also desirable to actually flush many records at once while still getting them one by one from the transformer.

YaEtl will call its `exec()` method (again inherited from NodalFlow's `ExecNodeInterface`) each time a record comes through, but will only call `flush()` once, after the loop ended. This is done like this to allow multi inserts/update logic where applicable. In such cases, there could be some left overs after the loop ends as the Loader would not actually flush records upon each call to `exec()`.

```php
use fab2s\YaEtl\LoaderAbstract;

/**
 * Class Loader
 */
class Loader extends LoaderAbstract
{
    /**
     * @var SuperCoolRepository
     */
    protected $repository;

    /**
     * @var mixed
     */
    protected $updateOrInsertData;

    /**
     * @return void
     */
    public function exec($record)
    {
        // the bellow example is trivial, but this is where
        // you can distinguish between updates and inserts
        // in order to implement multi inserts
        $this->updateOrInsertData = $record;
        $this->flush();
    }

    /**
     * This method is called at the end of the workflow in case the loader
     * needs to flush some remaining data from its eventual buffer.
     * Could be used to buffer records in order to perform multi inserts etc ...
     *
     * @param FlushStatusInterface $flushStatus the flush status, should only be set by
     *                             YaEtl to indicate last flush() call status
     *                             either :
     *                                  - clean (isClean()): everything went well
     *                                  - dirty (isDirty()): one extractor broke the flow
     *                                  - exception (isException()): an exception was raised during the flow
     *
     **/
    public function flush(FlushStatusInterface $flushStatus = null)
    {
        if ($flushStatus !== null) {
            /**
             * This is YaEtl's call to flush()
             * no need to further investigate here as
             * we did not buffer anything
             * but you could do things like:
             * if ($flushStatus->isException()) {
             *      return;
             * }
             *
             * The full story is :
             * if ($flowStatus !== null) {
             *      // YaEtl's call to flush()
             *      if ($flowStatus->isClean()) {
             *           // everything went well
             *      } elseif ($flowStatus->isDirty()) {
             *           // a node broke the flow
             *           // you may want not to insert left overs
             *      } elseif ($flowStatus->isException()) {
             *           // an exception was raised during the flow execution
             *      }
             * } else {
             *      // it should be you calling this method
             *      // during the flow execution (multi insert)
             * }
             */
            return;
        }

        // could be some kind of multi insert
        $this->repository->CreateOrUpdate($this->updateOrInsertData);
    }

}
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
    ->to(new AnotherLoader)
    // ...
    // optionally set callbacks
    ->setCallBack(new ClassImplementingCallbackInterface)
    // run ETL
    ->exec();

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

The later does not yet have a strict flow equivalent as Flows and Branches do not yet support traversability.

As every Node gets injected with the carrier flow, you can extend YaEtl to implement whatever context logic you could need to share among all nodes.
