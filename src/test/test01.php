<?php

use Jinraynor1\TableImporter\Drivers\DefaultDriver;
use Jinraynor1\TableImporter\Drivers\MySQL;
use Jinraynor1\TableImporter\Import;
use Jinraynor1\TableImporter\ConfigDatabase;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once __DIR__ . '/../../vendor/autoload.php';


$logger = new Logger("test01");
$logger->pushHandler(new StreamHandler('php://stdout'));


$source_config = new ConfigDatabase();
$source_config->setHost("localhost")
    ->setDatabaseName("mysql")
    ->setUsername("root")
    ->setPassword("root")
    ->setPort("3306")
    ->setDriver("mysql");

$target_config = $config = new ConfigDatabase();
$target_config->setHost("localhost")
    ->setDatabaseName("prueba")
    ->setUsername("root")
    ->setPassword("root")
    ->setPort("3306")
    ->setDriver("mysql");

$driver = new MySQL();



$import = new Import($source_config, $target_config);
$import->setImportOptions(Import::MODE_REPLACE);
$import->setLogger($logger);
$import->setTableName("user");
$import->setQuery("SELECT * FROM user");
$import->setImportDriver($driver);

echo "------ ADVANCED LOAD ----- " . PHP_EOL;
$driver->setOptions(MySQL::USE_ADVANCED_LOAD);
$import->run();
echo PHP_EOL;

echo "------ NORMAL INSERT ----- " . PHP_EOL;
$driver->setOptions(MYSQL::USE_NORMAL_INSERT);
$import->run();
echo PHP_EOL;

echo "------ MULTIPLE INSERT ----- " . PHP_EOL;
$driver->setOptions(MYSQL::USE_NORMAL_INSERT);
$import->run();
echo PHP_EOL;