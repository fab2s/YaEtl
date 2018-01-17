# Code re-usability

YaEtl allows vast possibilities to reuse the code once written for an ETL. You can for example use a loader to load one single $record, as you would use a Model to store a record in a database, either directly:

```php
$myLoader->exec($record)->flush();
```

 or wrapped in a flow:
 
```php
(new YaEtl)->to($myLoader)->exec($record);
```

And the same goes with transformers which can be used alone or grouped without together without the need of any other nodes in the flow:

```php
// single transform
$result = $aTransformer->exec($record);
// or chained transform
$result = $aTransformer->exec($anotherTransformer->exec($record));
// or chained transform wrapped in a flow
$result = (new YaEtl)->transform($aTransformer)->transform($anotherTransformer)->exec($record);
```

Even Extractors can be used directly as data generators:

```php
foreach ($myExtractor->getTraversable(null) as $record) {
    // do something with the record
}
```

All this means that while implementing ETL flows, you create other opportunities either withing or without the flow which will save more and more time over time.

And in fact, the overhead of doing so is very small, especially if your extractor is a [`Generator`](http://php.net/Generator) yielding values and is using [`SplDoublyLinkedList`](http://php.net/SplDoublyLinkedList) as record buffer as it is the case for every traversed extractor in YaEtl. Extractor joining against another one are using an associative array to be faster on key testing, but they are not traversed as they join their data on each record one by one (while still getting them by batch from the repository).
