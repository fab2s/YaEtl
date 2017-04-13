# YaEtl

[![Build Status](https://travis-ci.org/fab2s/YaEtl.svg?branch=master)](https://travis-ci.org/fab2s/YaEtl) [![HHVM](https://img.shields.io/hhvm/fab2s/YaEtl.svg)](http://hhvm.h4cc.de/package/fab2s/yaetl) [![Code Climate](https://codeclimate.com/github/fab2s/YaEtl/badges/gpa.svg)](https://codeclimate.com/github/fab2s/YaEtl) [![Codacy Badge](https://api.codacy.com/project/badge/Grade/aa2adb7aac514da497b154d6ad37db3c)](https://www.codacy.com/app/fab2s/YaEtl) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fab2s/YaEtl/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fab2s/YaEtl/?branch=master) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat)](http://makeapullrequest.com) [![License](https://poser.pugx.org/fab2s/nodalflow/license)](https://packagist.org/packages/fab2s/yaetl)

YaEtl ("Yay'TL", or YetAnotherEtl) is a PHP implementation of a widely extended Extract-Transform-Load (aka ETL) workflow based on [NodalFlow](https://github.com/fab2s/NodalFlow).
ETL workflows comes handy in numerous situations where a lot of records meet with various sources, format and repositories.
YaEtl widely extends this pattern allowing you to chain any number of E-T-L operation with an extra Join one allowing you to join records among extractors as you would do it with a DBMS. YaEtl can even just Extract and load with no transformation involved, or even just load or transform. If we where to acronym the workflow behind YaEtl, it could result in *NEJTL* for *Nodal-Extract-Join-Tranform-Load* workflow.

> [NodalFlow](https://github.com/fab2s/NodalFlow) was written while YaEtl was already started as it became clear that the pure executable flow logic would better be separated from it. The principle behind NodalFlow is simple, it's a directed graph composed of nodes which are somehow executable, accept one parameter and may be set to return a value that will be used as argument to the next node, or not, in which case the previous and untouched argument will be passed to the next node. Nodes can also be traversable (data generators etc ...) in which case they will be iterated over each of their values in the flow until they run out. When a node is "travsersed", each of the values yielded will trigger the execution of the successor nodes with or without the yielded value as argument, depending on the traversable node properties.

The major interest of such design is, in addition to organize complex task with ease, to create reusable and atomic tasks. Each node in the workflow will be reusable in any other workflow just and strictly as it is. And this can represent tremendous time saving along the way, actually, just more and more over time and as the code base grows.

Being Nodal makes it possible to chain arbitrary number of Extract to Load operations which may go through arbitrary number of transform, joins and, to even branch the workflow in case some Loaders require different transformation and or joins before they can do their work.

## Resources
 - [Documentation](https://github.com/fab2s/YaEtl/blob/master/docs/index.md)
 - [Usage](https://github.com/fab2s/YaEtl/blob/master/docs/usage.md)
 - [Code reusability](https://github.com/fab2s/YaEtl/blob/master/docs/reusability.md)
 - [Callbacks](https://github.com/fab2s/YaEtl/blob/master/docs/callbacks.md)
 - [Exceptions](https://github.com/fab2s/YaEtl/blob/master/docs/exceptions.md)
 - [Laravel (the awesome)](https://github.com/fab2s/YaEtl/blob/master/docs/laravel.md)


## Installation

YaEtl can be installed using composer :

```shell
composer require "fab2s/yaetl"
```

But until NodalFlow and YaEtl hits stable release, you will also need to specifically require NodalFlow:
```shell
composer require "fab2s/nodalflow"
```

This is required because YaEtl depends on another "unstable" (even though, test are showing that something is working) package would otherwise not be explicitly required as "unstable" by the project using YaEtl.

Once done, you can start playing :

```php
$yaEtl = new YaEtl;
$yaEtl->from(new Extractor)
    -> transform(new Transformer)
    ->to(new Loader)
    ->exec();

// forgot something ?
// continuing with the same object
$yaEtl->transform($anotherTransformer = new AnotherTransformer)
    ->to(new CsvLoader)
    ->transform(new SuperCoolTransformer)
    ->to(new S3Loader)
    // never too cautious
    ->to(new FlatLogLoader)
    // better
    ->exec();

// oh but what if ...
$yaEtl->branch(
    (new YaEtl)->transform(new SwaggyTransformer)
        // we need to enrich $anotherTransformer's records
        ->join($anotherTransformer, new HypeJoiner($pdo, $query, new OnClose('upstreamFieldName', 'joinerFieldName', function($upstreamRecord, $joinerRecord) {
            return array_replace($joinerRecord, $upstreamRecord);
        })))
        ->transform(new PutItAllTogetherTransformer)
        ->to(new SuperSpecializedLoader)
    )->exec();

// etc ...
```

## Serialization

As the whole flow is an object, it can be serialized, but this is unless it carries Closures, which may occur with `OnClose` objects. Closure serialization is not natively supported by PHP, but there are ways around it like [Opis Closure](https://github.com/opis/closure)

## Requirements

NodalFlow is tested against php 5.6, 7.0, 7.1 and hhvm, but it may run bellow that (might up to 5.3).

## Contributing

Contributions are welcome. A great way to give back would be to share the generic extractors (Redis, RedShift, LDAP etc ...) you may write while using YaEtl as it would directly benefit to everybody.
In all cases, do not hesitate to open issues and submit pull requests.

## License

NodalFlow is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
