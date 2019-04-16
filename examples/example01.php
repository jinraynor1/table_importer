<?php

use Jinraynor1\TableImporter\Drivers\DefaultDriver;
use Jinraynor1\TableImporter\Drivers\MySQL;
use Jinraynor1\TableImporter\Import;
use Jinraynor1\TableImporter\ConfigDatabase;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once __DIR__ . '/../../vendor/autoload.php';


$logger = new Logger("example01");
$logger->pushHandler(new StreamHandler('php://stdout'));

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


/**
 * Try all insert mode available
 */


echo "------ ADVANCED LOAD ----- " . PHP_EOL;
$driver->setInsertModeAdvanced();
$import->run();
echo PHP_EOL;

echo "------ NORMAL INSERT ----- " . PHP_EOL;
$driver->setInsertModeBasic();
$import->run();
echo PHP_EOL;

echo "------ MULTIPLE INSERT ----- " . PHP_EOL;
$driver->setInsertModeMultiple();
$import->run();
echo PHP_EOL;