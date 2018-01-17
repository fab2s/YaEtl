# Laravel (the awesome)

This package comes with ready and easy to use [**Laravel**](https://laravel.com/) implementations.

## Extractor

Laravel's Extractor extend their PDO counterpart and do all the actual querying using PDO directly. This is done so because they are intended to extract up to many records, and the overhead of using collections and objects for every batch of record would be pretty high. Actually, it could become a problem over couple 100k records, would create less reusable code and without real benefit (the data stays the same).

This means that these class do not differ much from the PDO implementations, as they essentially implement logic to extract the underlying PDO object, query and bindings from Laravel's `Builder`. But they also demonstrate how you could extends the various Abstracts, Interfaces and implementations to create your own.

### Generic DB extractor

```php
use fab2s\YaEtl\YaEtl;
use fab2s\YaEtl\Laravel\DbExtractor;
use DB;

// define extract query, MUST be explicitly ordered for pagination
// to be fully consistent.
// Worth nothing to say that you want the query object here
// not the result (ie DO NOT use get, first etc ...)
$extractQuery = DB::table('mytable')
    ->select([
        'mytable.field1',
        'mytable.field2',
        // ..
    ])
    ->where('mytable.field1', '=', 'someValue')
    ->orderBy('mytable.id', 'asc');

// instantiate the generic db extractor
$dbExtractor = new DbExtractor;

// set extract query and fetch 5000 records at a time
$dbExtractor->setExtractQuery($extractQuery)->setBatchSize(5000);

// run the ETL
$yaEtl = new YaEtl;
$yaEtl->from($dbExtractor)
    ->transform(new Transformer)
    ->to(new Loader)
    ->exec();
```

### Unique Key extractor

In many simple case you can use the handy `UniqueKeyExtractor` which also implement `JoinableInterface`

```php
use fab2s\YaEtl;
use fab2s\YaEtl\Laravel\Extractors\UniqueKeyExtractor;
use DB;

// Note that UniqueKeyExtractor also supports composite (primary|unique) keys
$extractQuery = DB::table('mytable')
    ->select([
        'mytable.field1',
        'mytable.field2',
        // ..
    ])
    ->where('mytable.field1', '=', 'someValue')
    ->orderBy('mytable.id', 'asc');
$uniqueKeyExtractor = new UniqueKeyExtractor($extractQuery, 'id');

// fetch 5000 records at a time
$uniqueKeyExtractor->setBatchSize(5000);

// run the ETL
$yaEtl = new \fab2s\YaEtl\YaEtl;
$yaEtl->from($uniqueKeyExtractor)
    ->transform(new Transformer)
    ->to(new Loader)
    ->exec();

```

## Loader

### Generic DB loader

```php
use fab2s\YaEtl;
use fab2s\YaEtl\Laravel\Loaders\DbLoader;
use DB;

// define load query
$loadQuery = DB::table('mytable');

// instantiate the generic db loader
$dbLoader = new DbLoader($loadQuery);

// set load query and the fields to use in the update where/insert field list
// clause
$dbLoader->setLoadQuery($loadQuery)->setWhereFields([
    'mytable.date',
    'mytable.other_field',
    // ...
]);

// run the ETL
$yaEtl = new \fab2s\YaEtl\YaEtl;
$yaEtl->from(new Extractor)
    ->transform(new Transformer)
    ->to($dbLoader)
    ->exec();
```
