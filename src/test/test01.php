<?php

use Jinraynor1\TableImporter\AbstractImport;
use Jinraynor1\TableImporter\ConfigDatabase;
use Jinraynor1\TableImporter\ImportInterface;
use Jinraynor1\TableImporter\ImportTrait;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once __DIR__ . '/../../vendor/autoload.php';


class test01 extends AbstractImport implements ImportInterface
{

    use ImportTrait;

    public function __construct()
    {
        $import_options = self::MODE_REPLACE;
        parent::__construct($import_options);
    }

    function getSourceDatabaseConnection()
    {

        $config = (new ConfigDatabase())
            ->setHost("localhost")
            ->setDatabaseName("test")
            ->setUsername("root")
            ->setPassword("")
            ->setPort("3306")
            ->setDriver("mysql");


        return $this->openDB($config);
    }

    function getTargetDatabaseConnection()
    {
        $config = (new ConfigDatabase())
            ->setHost("localhost")
            ->setDatabaseName("prueba")
            ->setUsername("root")
            ->setPassword("")
            ->setPort("3306")
            ->setDriver("mysql");


        return $this->openDB($config);
    }

    function getTableName()
    {
        return "borrame";
    }

    function getSqlPullData()
    {
        return "SELECT a FROM borrame";
    }
}



$test01 = new test01();
$logger= new Logger("test01");
$logger->pushHandler(new StreamHandler('php://stdout'));
$test01->setLogger($logger);
$test01->run();