---
title: Gettins Started
parent: Index
has_children: false
nav_order: 2
---

# Getting Started


We first need to create source connection  and remote connection

```php
<?php

/**
 * Configure source
 */
$source_config = new ConfigDatabase();
$source_config->setHost("localhost")
    ->setDatabaseName("mysql")
    ->setUsername("root")
    ->setPassword("root")
    ->setPort("3306")
    ->setDriver("mysql");

/**
 * Configure target
 */

$target_config = $config = new ConfigDatabase();
$target_config->setHost("localhost")
    ->setDatabaseName("prueba")
    ->setUsername("root")
    ->setPassword("root")
    ->setPort("3306")
    ->setDriver("mysql");

```   


Now we should specify the driver and tell the importer we are using it, optionally we can attach a log

```php
<?php
/**
 * Init driver an create importer
 */
$driver = new MySQL();

$import = new Import($source_config, $target_config);
$import->setImportModeIsReplace()
    ->setTableName("user")
    ->setQuery("SELECT * FROM user")
    ->setImportDriver($driver)
    ->setLogger($logger);


$driver->setInsertModeAdvanced();
$import->run();
```
