# YaEtl

[![Documentation Status](https://readthedocs.org/projects/yaetl/badge/?version=latest)](http://yaetl.readthedocs.io/en/latest/?badge=latest) [![Build Status](https://travis-ci.org/fab2s/YaEtl.svg?branch=master)](https://travis-ci.org/fab2s/YaEtl) [![Total Downloads](https://poser.pugx.org/fab2s/yaetl/downloads)](https://packagist.org/packages/fab2s/yaetl) [![Monthly Downloads](https://poser.pugx.org/fab2s/yaetl/d/monthly)](https://packagist.org/packages/fab2s/yaetl) [![Latest Stable Version](https://poser.pugx.org/fab2s/yaetl/v/stable)](https://packagist.org/packages/fab2s/yaetl) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/1f24395f-9b33-4d99-acc7-d286a5f54db4/mini.png)](https://insight.sensiolabs.com/projects/1f24395f-9b33-4d99-acc7-d286a5f54db4) [![Code Climate](https://codeclimate.com/github/fab2s/YaEtl/badges/gpa.svg)](https://codeclimate.com/github/fab2s/YaEtl) [![Codacy Badge](https://api.codacy.com/project/badge/Grade/aa2adb7aac514da497b154d6ad37db3c)](https://www.codacy.com/app/fab2s/YaEtl) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fab2s/YaEtl/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fab2s/YaEtl/?branch=master) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat)](http://makeapullrequest.com) [![License](https://poser.pugx.org/fab2s/nodalflow/license)](https://packagist.org/packages/fab2s/yaetl)

`YaEtl` ("Yay'TL", or YetAnotherEtl) is a PHP implementation of an Extract-Transform-Load (aka ETL) workflow based on [NodalFlow](https://github.com/fab2s/NodalFlow).
ETL workflow comes handy in numerous situations where a lot of records meet with various sources, format and repositories.
`YaEtl` extends this pattern allowing you to chain any number of E-T-L operations with some extra capabilities such as Joining and Qualifying. `YaEtl` can even just Extract and load with no transformation involved, or even just load or transform. If we where to acronym the workflow behind `YaEtl`, it could result in *NEJQTL* for *Nodal-Extract-Join-Qualify-Transform-Load* workflow.

> [NodalFlow](https://github.com/fab2s/NodalFlow) is the underlying and even more generic implementation of an executable directed graph upon which is build `YaEtl`. The directed graphs are composed of Nodes which are somehow executable, accept one parameter and may be set to return a value that will be used as argument to the next Node; or not, in which case the previous and untouched argument will be passed to the next Node up to the Flow exec argument if any. Nodes can also be traversable (data generators etc ...) in which case they will be iterated over each of their values in the flow until they run out. When a node is "traversed", each of the values yielded will trigger the execution of the successor Nodes with or without the yielded value as argument, depending on the traversable node properties. Each of these directed graph can be invoked by any other instance in the process as well as by each Nodes and at any Node position, which effectively can turn any set of such graphs into an executable network of Nodes.

The major interest of such design is, in addition to organize complex task with ease, to create reusable and atomic tasks. Each Node in the workflow will be reusable in any other workflow just and strictly as it is. And this can represent tremendous time saving along the way, actually, just more and more over time and as the code base grows.

Being Nodal makes it possible to chain arbitrary number of Extract to Load operations which may go through arbitrary number of transform, joins and, to even branch the workflow in case some Loaders require different transformation and or joins before they can do their work.

## YaEtl Documentation
[![Documentation Status](https://readthedocs.org/projects/yaetl/badge/?version=latest)](http://yaetl.readthedocs.io/en/latest/?badge=latest) Documentation can be found at [ReadTheDocs](http://yaetl.readthedocs.io/en/latest/?badge=latest)
It is also a good thing to check [NodalFlow documentation](http://nodalflow.readthedocs.io/en/latest/?badge=latest), especially concerning fundamental features which are directly usable in `YaEtl` such as [Interruption](http://nodalflow.readthedocs.io/en/latest/interruptions/), [Serialization](http://nodalflow.readthedocs.io/en/latest/serialization/) or the [`sendTo()`](http://nodalflow.readthedocs.io/en/latest/usage/#the-sendto-methods) method allowing you to turns your Flows into _executable networks_ of Flows and Nodes.

## Installation

`YaEtl` can be installed using composer:

```shell
composer require "fab2s/yaetl"
```

If you want to specifically install the php >=7.1.0 version, use:

```
composer require "fab2s/yaetl" ^2
```

If you want to specifically install the php 5.6/7.0 version, use:

```
composer require "fab2s/yaetl" ^1
```

Once done, you can start playing :

```php
$yaEtl = new YaEtl;
$yaEtl->from($extractor = new Extractor)
    -> transform(new Transformer)
    ->to(new Loader)
    ->exec();

// forgot something ?
// continuing with the same object
$yaEtl->transform(new AnotherTransformer)
    ->to(new CsvLoader)
    ->transform(new SuperCoolTransformer)
    ->to(new S3Loader)
    // never too cautious
    ->to(new FlatLogLoader)
    // Flows are repeatable
    ->exec();

// oh but what if ...
$yaEtl->branch(
        (new YaEtl)->transform(new SwaggyTransformer)
        // Enrich $extractor's records
        ->join($extractor, new HypeJoiner($pdo, $query, new OnClose('upstreamFieldName', 'joinerFieldName', function($upstreamRecord, $joinerRecord) {
            return array_replace($joinerRecord, $upstreamRecord);
        })))
        ->transform(new PutItAllTogetherTransformer)
        ->to(new SuperSpecializedLoader)
)->exec();

// or another branch for a subset of the extraction
$yaEtl->branch(
    (new YaEtl)->qualify(new CallableQualifier(function($record) {
            return !empty($record['is_great']);
        })
        ->transform(new GreatTransformer)
        ->to(new GreatLoader)
)->exec();

// need a Progress Bar ?
$progressSubscriber = new ProgressBarSubscriber($yaEtl);
// with count ?
$progressSubscriber->setNumRecords($count);
```

## Usage Patterns

`YaEtl` can address several generic use cases with ease, among which some would otherwise require more specialized / complex coding.

### Pure ETL

`YaEtl` can, but is not limited to, incarnate a pure ETL flow as seen many times. It's worth nothing to say that `YaEtl` supports batch extract and load by design.

```
+---------+                   +---------+
|         |                   |         |
|         |                   |         |
|  data   |                   |  data   |
| source  |                   | storage |
|         |                   |         |
|         |                   |         |
|         |                   |         |
++-+-+-+-++                   +-^-^-^-^-+
 | + + + |                      + + + +
 |Records|                      Records
 | + + + |                      + + + +
+v-v-v-v-v--+                 +-+-+-+-++
|  Extract  |                 |  Load  |
+-+---------+                 +---^----+
  |                               |
  | Record  +-----------+ Record  |
  +---------> Transform +---------+
            +-----------+

```

### Shared extracts

Being Nodal makes it possible for `YaEtl` to transparently share extraction across as many use case as necessary, which in the end may even not be a load.

```
+-------------+
|             |
|             |
|  slow http  |
| data source |
|             |
|             |
+-----+-------+
      |
      |                            +----------+
+-----v-------+                 +--> Loader 1 |
|  Extractor  |                 |  +----------+
+-+-----------+                 |
  |                             |  +----------+
  |  Record 1  +-------------+  +--> Loader 2 |
  +------------> Transformer +--+  +----------+
  |            +-------------+  |      ...
  |  Record 2                   |
  +------------>                |  +----------+
  |    ...                      +--> Loader N |
  |                             |  +----------+
  |  Record N                   |
  +------------>                |  +-------------+
                                +--> Transformer |
                                   +-+-----------+
                                     |            +----------+
                                     +------------> Loader X |
                                                  +----------+

```

### Categorized Extract

As Extractors will have the up stream return value as argument, it is possible to chain Extractors themselves to obtain items in categories. This can help separate concerns as it makes it possible to extract all items in all categories while still using specialized extractors, eg a category and an item one; provided that the item extractor is also able to extract items by category when passed with the proper category object as argument (which is not the case when you would start with extracting items, unless you specify an argument to the whole flow).

```
+------------+
|            |
|            |
| categories |
|            |
|            |
+-----+------+
      |
      |
+-----v------+
| Extractor  |
++-----------+
 |
 | category   +-----------+
 +------------> Extractor |
 |            ++----------+
 +-----------> |
      ...      |  item
               +----------> ...
               |
               +---------->
                    ...

```

### Sharded extraction

Some time, it could be required to extract data from several physical sources and / or shards at a low level, that is without any predefined and ready to use abstraction.

This kind of operation is easy with `YaEtl` as Extractors can be aggregated to each other when building the flow. You could for example wish to extract data spanning over several sources where each would only keep a specific time frame. The same extractor could then be instantiated for each shard with proper sorting to end up extracting all the data as if it was stored in a single repository. `YaEtl` would then internally consume each extractor's records in the order they where added to the flow and provide them one by one to the remaining nodes strictly as if a single extractor was used.

```
     +-------------+  +-------------+     +-------------+
     |             |  |             |     |             |
     |   shard 1   |  |   shard 2   | ... |   shard N   |
     |             |  |             |     |             |
     +------+------+  +------+------+     +------+------+
            |                |                   |
            |                |                   |
+------------------------------------------------------------+
|           |                |                   |           |
|    +------v------+  +------v------+     +------v------+    |
|    |             |  |             |     |             |    |
|    | Extractor 1 |  | Extractor 2 | ... | Extractor N |    |
|    |             |  |             |     |             |    |
|    +------+------+  +------+------+     +-------+-----+    |
|           |                |                    +          |
|           |                |             Aggregate Node    | Records
+-----------v----------------v--------------------v----------+---------->

                                                                       ...

```

### Joins

`YaEtl` provides with all the necessary interfaces to implement Join operation in pretty much the same way a DBMS would (regular and left join). Under the hoods, this require to communicate some kind of record map for joiners to know what record to match in the process. `YaEtl` comes with a complete `PDO` implementation of a generic Join-able Extractor (against single unique key). Use cases of such feature are endless, especially when you start considering that all the above patterns are fully combine-able and even branch-able. It is also important to note that `YaEtl` extractors support extracting records by batches even for joiners which could (and most likely should) be smaller than the extractor joined against (eg smaller sets for `WHERE IN` query types).

```
+-----------+      +------------+
|           |      |            |
|           |      |            |
|   users   |      | addresses  |
|           |      |            |
|           |      |            |
+-----+-----+      +-----+------+
      |                  |
      |                  |
+-----v-----+ user +-----v------+ user & address
| Extractor +------>   Joiner   +---------------->
+-----------+      +------------+

```

## Qualification

`YaEtl` (>= 1.1.0) introduces a `QualifierInterface` partially implemented by `QualifierAbstract` and directly usable with the `CallableQualifier` class. Qualifiers aims at increasing the separation of concerns between Flow conditions (IFs) and Flow actions (Transform and Load), which in return should help out writing more general Transformers and Loaders (which do not need to hold every conditions anymore) and thus increase re-usability.

Using such Node, you can for example share a slow extraction among many usages of the same record by just instantiating one Branch per scenario, each starting with a Qualifier in charge of accepting or not the record based on its properties.

```
                    +------------------------------------------------+
+-----------+       |  +----------+                                  |
| Extractor +----+----->Qualifier1+--->... Transform ... ---> Loader1|
+-----------+    |  |  +----------+                      branch1     |
                 |  +------------------------------------------------+
                 |
                 |  +------------------------------------------------+
                 |  |  +----------+                                  |
                 +----->Qualifier2+--->... Transform ... ---> Loader2|
                 |  |  +----------+                      branch2     |
                 |  +------------------------------------------------+
                 |
                 |  +------------------------------------------------+
                 |  |  +----------+                                  |
                 +----->QualifierN+--->... Transform ... ---> LoaderN|
                    |  +----------+                      branchN     |
                    +------------------------------------------------+

```

In this example, each record would be presented to every branch and each Qualifier would be in charge of accepting the record in its Branch for other Nodes to act on it. As you can see, this pattern creates a lot of occasions to reuse existing Nodes as downstream Transformers and Loaders do not have to know anything about the specific properties we where choosing in the Qualifier. This means that you can write very generic loader strictly in charge of loading a record somewhere, leave the defaulting and formatting (charset etc) to a Transformer that does just that, and reuse these in any conditional use case by just Implementing a qualifier that holds the conditional logic.

Read [Qualifiers](/docs/citizens.md#qualifiers) for more on qualification.

## Serialization

`YaEtl` is serializable, but this is unless it carries Closures, which may occur with `OnClose` objects, as `Closure` serialization is not natively supported by PHP, but there are ways around it like [Opis Closure](https://github.com/opis/closure).

Please have a look at [NodalFlow documentation](https://github.com/fab2s/NodalFlow/blob/master/docs/serialization.md) for more interesting edge cases with serialization.

## Requirements

`YaEtl` is tested against php 7.1, 7.2, 7.3 and 7.4

## Contributing

Contributions are welcome. A great way to give back would be to share the generic extractors (Redis, RedShift, LDAP etc ...) you may write while using `YaEtl` as it would directly benefit to everybody.
In all cases, do not hesitate to open issues and submit pull requests.

## License

`YaEtl` is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
