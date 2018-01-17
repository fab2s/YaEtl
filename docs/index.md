# YaEtl

[![Documentation Status](https://readthedocs.org/projects/yaetl/badge/?version=latest)](http://yaetl.readthedocs.io/en/latest/?badge=latest) [![Build Status](https://travis-ci.org/fab2s/YaEtl.svg?branch=master)](https://travis-ci.org/fab2s/YaEtl) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/1f24395f-9b33-4d99-acc7-d286a5f54db4/mini.png)](https://insight.sensiolabs.com/projects/1f24395f-9b33-4d99-acc7-d286a5f54db4) [![Code Climate](https://codeclimate.com/github/fab2s/YaEtl/badges/gpa.svg)](https://codeclimate.com/github/fab2s/YaEtl) [![Codacy Badge](https://api.codacy.com/project/badge/Grade/aa2adb7aac514da497b154d6ad37db3c)](https://www.codacy.com/app/fab2s/YaEtl) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fab2s/YaEtl/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fab2s/YaEtl/?branch=master) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat)](http://makeapullrequest.com) [![License](https://poser.pugx.org/fab2s/nodalflow/license)](https://packagist.org/packages/fab2s/yaetl)

YaEtl ("Yay'TL", or YetAnotherEtl) is a PHP implementation of a widely extended Extract-Transform-Load (aka ETL) workflow based on [NodalFlow](https://github.com/fab2s/NodalFlow).
ETL workflows comes handy in numerous situations where a lot of records meet with various sources, format and repositories.
YaEtl widely extends this pattern allowing you to chain any number of E-T-L operation with an extra Join one allowing you to join records among extractors as you would do it with a DBMS. YaEtl can even just Extract and load with no transformation involved, or even just load or transform. If we where to acronym the workflow behind YaEtl, it could result in *NEJTL* for *Nodal-Extract-Join-Tranform-Load* workflow.

> [NodalFlow](https://github.com/fab2s/NodalFlow) was written while YaEtl was already started as it became clear that the pure executable flow logic would better be separated from it. The principle behind NodalFlow is simple, it's a directed graph composed of nodes which are somehow executable, accept one parameter and may be set to return a value that will be used as argument to the next node, or not, in which case the previous and untouched argument will be passed to the next node. Nodes can also be traversable (data generators etc ...) in which case they will be iterated over each of their values in the flow until they run out. When a node is "travsersed", each of the values yielded will trigger the execution of the successor nodes with or without the yielded value as argument, depending on the traversable node properties.

The major interest of such design is, in addition to organize complex task with ease, to create reusable and atomic tasks. Each node in the workflow will be reusable in any other workflow just and strictly as it is. And this can represent tremendous time saving along the way, actually, just more and more over time and as the code base grows.

Being Nodal makes it possible to chain arbitrary number of Extract to Load operations which may go through arbitrary number of transform, joins and, to even branch the workflow in case some Loaders require different transformation and or joins before they can do their work.

## Installation

YaEtl can be installed using composer :

```shell
composer require "fab2s/yaetl"
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

// etc ...
```

## Usage Pattern

YaEtl can address several generic use cases with ease, among which some would otherwise require more specialized / complex coding.

### Pure ETL

YaEtl can, but is not limited to, incarnate a pure ETL flow as seen many times. It's worth nothing to say that YaEtl supports batch extract and load by design.
```bash
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

### Mutualized extracts

Being Nodal makes it possible for YaEtl to transparently mutualize extraction across as many use case as necessary, which in the end may even not be a load.
```bash
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

```bash
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

This kind of operation is easy with YaEtl as Extractors can be aggregated to each other when building the flow. You could for example wish to extract data spanning over several sources where each would only keep a specific time frame. The same extractor could then be instantiated for each shard with proper sorting to end up extracting all the data as if it was stored in a single repository. YaEtl would then internally consume each extractor's records in the order they where added to the flow and provide them one by one to the remaining nodes strictly as if a single extractor was used.

```bash
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

YaEtl provides with all the necessary interfaces to implement Join operation in pretty much the same way a DBMS would (regular and left join). Under the hoods, this require to communicate some kind of record map for joiners to know what record to match in the process. YaEtl comes with a complete `PDO` implementation of a generic Joinable Extractor (against single unique key). Use cases of such feature are endless, especially when you start considering that all the above patterns are fully combinable and even branchable. It is also important to note that YaEtl extractors support extracting records by batches even for joiners which could (and most likely should) be smaller than the extractor joined against (eg smaller sets for `WHERE IN` query types).

```bash
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

## Serialization

YaEtl is serializable, but this is unless it carries Closures, which may occur with `OnClose` objects, as `Closure` serialization is not natively supported by PHP, but there are ways around it like [Opis Closure](https://github.com/opis/closure).

Please have a look at [NodalFlow documentation](https://github.com/fab2s/NodalFlow/blob/master/docs/serialization.md) for more interesting edge cases with serialization.

## Requirements

NodalFlow is tested against php 5.6, 7.0, 7.1, 7.2 and hhvm, but it may run bellow that (might up to 5.3).

## Contributing

Contributions are welcome. A great way to give back would be to share the generic extractors (Redis, RedShift, LDAP etc ...) you may write while using YaEtl as it would directly benefit to everybody.
In all cases, do not hesitate to open issues and submit pull requests.

## License

NodalFlow is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
