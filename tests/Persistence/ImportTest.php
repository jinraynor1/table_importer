<?php

use Jinraynor1\TableImporter\Drivers\MySQL;
use Jinraynor1\TableImporter\Import;
use Jinraynor1\TableImporter\ConfigDatabase;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;


class ImportTest extends TestCase
{
    /**
     * @var MySQL
     */
    protected static $driver;
    /**
     * @var Import
     */
    protected static $importer;
    protected static $driver_name = 'mysql';



    public static function setUpBeforeClass()
    {
        self::initDatabase();

        $logger = new Logger("example01");
        $logger->pushHandler(new StreamHandler('php://stdout',LOGGER::CRITICAL));

        /**
         * Configure source
         */
        $source_config = new ConfigDatabase();
        $source_config->setHost($GLOBALS["DB_HOST"])
            ->setDatabaseName($GLOBALS["DB_NAME"])
            ->setUsername($GLOBALS["DB_USER"])
            ->setPassword($GLOBALS["DB_PASS"])
            ->setPort($GLOBALS["DB_PORT"])
            ->setDriver("mysql");

        /**
         * Configure target
         */

        $target_config = $config = new ConfigDatabase();
        $target_config->setHost($GLOBALS["DB_HOST"])
            ->setDatabaseName($GLOBALS["DB_NAME"])
            ->setUsername($GLOBALS["DB_USER"])
            ->setPassword($GLOBALS["DB_PASS"])
            ->setPort($GLOBALS["DB_PORT"])
            ->setDriver("mysql");


        /**
         * Init driver an create importer
         */
        self::$driver = new MySQL();

        self::$importer = new Import($source_config, $target_config);
        self::$importer->setImportModeIsReplace()
            ->setTableName("target_table")
            ->setQuery("SELECT * FROM source_table")
            ->setImportDriver(self::$driver)
            ->setLogger($logger);

    }

    public static function initDatabase()
    {
        $dsn  = self::$driver_name .":host=".$GLOBALS["DB_HOST"].";dbname=" . $GLOBALS["DB_NAME"];
        $pdo = new PDO($dsn,$GLOBALS["DB_USER"],$GLOBALS["DB_PASS"]);
        $querys = array();
        $querys[] = "CREATE TABLE IF NOT EXISTS source_table (colA INT UNSIGNED)";
        $querys[] = "CREATE TABLE IF NOT EXISTS target_table (colA INT UNSIGNED)";

        $querys[] = "TRUNCATE TABLE source_table";
        $querys[] = "TRUNCATE TABLE target_table";

        $querys[] = "INSERT INTO source_table VALUES(1),(2)";


        foreach ($querys as $query) {
            $pdo->query($query);
        }

    }


    public function testInsertModeNormal()
    {
        self::$driver->setInsertModeBasic();
        $imported_lines = self::$importer->run();

        $this->assertEquals(2, $imported_lines);

    }

    public function testInsertModeMultiple()
    {
        self::$driver->setInsertModeMultiple();
        $imported_lines = self::$importer->run();

        $this->assertEquals(2, $imported_lines);

    }

    public function testInsertModeAdvanced()
    {
        self::$driver->setInsertModeAdvanced();
        $imported_lines = self::$importer->run();

        $this->assertEquals(2, $imported_lines);

    }

    public function testCallbackBeforePush()
    {
        self::$driver->setInsertModeBasic();


        self::$importer->callback_before_push_data = function(SplFileInfo $file) {

            $lines = file($file->getRealPath());
            $last = sizeof($lines) - 1 ;
            unset($lines[$last]);


            $fp = fopen($file->getRealPath(), 'w');
            fwrite($fp, implode('', $lines));
            fclose($fp);
        };
        $imported_lines = self::$importer->run();

        $this->assertEquals(1, $imported_lines);
    }

}