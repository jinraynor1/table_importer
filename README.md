# table_importer
Imports data from remote source in a generic way
## Getting Started

Brief example of how to use
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
Please see tests directory for more example on how to use this library
