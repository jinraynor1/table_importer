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

    /**
     * @var ConfigDatabase
     */
    protected static $source_config;

    /**
     * @var ConfigDatabase
     */
    protected static $target_config;


    /**
     * @var Monolog\Logger
     */
    protected static $logger;

    protected static $driver_name = 'mysql';

    /**
     * @var PDO
     */
    protected static $pdo;


    public function setUp()
    {
        self::initDatabase();

    }
    public static function setUpBeforeClass()
    {

        self::$logger = new Logger("example01");
        self::$logger->pushHandler(new StreamHandler('php://stdout',LOGGER::CRITICAL));

        /**
         * Configure source
         */
        self::$source_config = new ConfigDatabase();
        self::$source_config->setHost($GLOBALS["DB_HOST"])
            ->setDatabaseName($GLOBALS["DB_NAME"])
            ->setUsername($GLOBALS["DB_USER"])
            ->setPassword($GLOBALS["DB_PASS"])
            ->setPort($GLOBALS["DB_PORT"])
            ->setDriver("mysql");

        /**
         * Configure target
         */

        self::$target_config =  new ConfigDatabase();
        self::$target_config->setHost($GLOBALS["DB_HOST"])
            ->setDatabaseName($GLOBALS["DB_NAME"])
            ->setUsername($GLOBALS["DB_USER"])
            ->setPassword($GLOBALS["DB_PASS"])
            ->setPort($GLOBALS["DB_PORT"])
            ->setDriver("mysql");


        /**
         * Init driver an create importer
         */
        self::$driver = new MySQL();

        self::$importer = new Import(self::$source_config, self::$target_config);
        self::$importer->setImportModeIsReplace()
            ->setTableName("target_table")
            ->setQuery("SELECT * FROM source_table")
            ->setImportDriver(self::$driver)
            ->setLogger(self::$logger);

    }

    public static function initDatabase()
    {
        $dsn  = self::$driver_name .":host=".$GLOBALS["DB_HOST"].";dbname=" . $GLOBALS["DB_NAME"];
        self::$pdo = new PDO($dsn,$GLOBALS["DB_USER"],$GLOBALS["DB_PASS"]);
        $querys = array();

        $querys[] = "DROP TABLE IF EXISTS source_table";
        $querys[] = "DROP TABLE IF EXISTS target_table";

        $querys[] = "CREATE TABLE IF NOT EXISTS source_table (colA INT UNSIGNED)";
        $querys[] = "CREATE TABLE IF NOT EXISTS target_table (colA INT UNSIGNED)";

        $querys[] = "TRUNCATE TABLE source_table";
        $querys[] = "TRUNCATE TABLE target_table";

        $querys[] = "INSERT INTO source_table VALUES(1),(2)";


        foreach ($querys as $query) {
            self::$pdo->query($query);
        }

    }

    public function testCreateFromConnection()
    {
        $format = "mysql:host=%s;dbname=%s;port=%s";
        $dsn = sprintf($format,
            self::$source_config->getHost(),
            self::$source_config->getDatabaseName(),
            self::$source_config->getPort()
        );
        $db_source = new \PDO($dsn,self::$source_config->getUsername(),self::$source_config->getPassword());
        $db_target = new \PDO($dsn,self::$source_config->getUsername(),self::$source_config->getPassword());
        $importer = Import::createFromConnection($db_source, $db_target);

        $importer->setImportModeIsReplace()
            ->setTableName("target_table")
            ->setQuery("SELECT * FROM source_table LIMIT 1")
            ->setLogger(self::$logger);
        $imported_lines = $importer->run();

        $this->assertEquals(1, $imported_lines);
    }

    public function testModeAppend()
    {
        $imported_lines =  self::$importer->setImportModeIsAppend()
            ->run();

        $this->assertEquals(2, $imported_lines);


    }

    public function testTemporaryFileIsRemoved()
    {
        self::$importer->setImportModeIsReplace()
            ->run();

        $this->assertFalse(file_exists(self::$importer->getTmpFile()));
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


        self::$importer->setCallbackBeforePushData( function(SplFileInfo $file) {

            $lines = file($file->getRealPath());
            $last = sizeof($lines) - 1 ;
            unset($lines[$last]);


            $fp = fopen($file->getRealPath(), 'w');
            fwrite($fp, implode('', $lines));
            fclose($fp);
        });
        $imported_lines = self::$importer->run();

        $this->assertEquals(1, $imported_lines);
    }

    /**
     * @expectedException Exception
     */
    public function testSetInvalidTmpDir()
    {
        self::$driver->setInsertModeBasic();
        $custom_tmp_dir = sys_get_temp_dir()."/subdir1/subdir2/";
        if(file_exists($custom_tmp_dir)){
            $this->rmdir_recursive($custom_tmp_dir);
        }

        $this->expectException("Exception");
        self::$importer->setTmpDir($custom_tmp_dir);


    }

    public function testCanSetTmpDir()
    {
        $custom_tmp_dir = sys_get_temp_dir().DIRECTORY_SEPARATOR."subdir1".DIRECTORY_SEPARATOR."subdir2".DIRECTORY_SEPARATOR;
        if(file_exists($custom_tmp_dir)){
            $this->rmdir_recursive($custom_tmp_dir);
        }
        mkdir($custom_tmp_dir,0777,true);

        $that = $this;
        self::$importer->setTmpDir($custom_tmp_dir);
        self::$importer->setCallbackBeforePushData( function(SplFileInfo $file) use($that, $custom_tmp_dir){
            $path = $file->getPath();
            $path = rtrim($path,DIRECTORY_SEPARATOR);
            $custom_tmp_dir = rtrim($custom_tmp_dir,DIRECTORY_SEPARATOR);
            $that->assertSame($custom_tmp_dir, $path);
        });

        $imported_lines = self::$importer->run();
        $this->assertEquals(2, $imported_lines);
        $this->rmdir_recursive($custom_tmp_dir);



    }


    private function rmdir_recursive($dir) {
        $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($it as $file) {
            if ($file->isDir()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
        rmdir($dir);
    }

    public function testCanGetRecordsFounded()
    {

        self::$driver->setInsertModeBasic();
        $that = $this;
        $importer = self::$importer;
        self::$importer->setCallbackBeforePushData( function(SplFileInfo $file) use($that, $importer) {
            $records_founded = $importer->getRecordsFounded();
            $that->assertSame(2, $records_founded);
        });
        $imported_lines = self::$importer->run();

    }
    public function testCallbackRow()
    {
        self::$driver->setInsertModeMultiple();

        self::$pdo->query("ALTER TABLE target_table ADD COLUMN colB INT UNSIGNED");

        self::$importer->setCallbackRow( function($row) {
            $row['colA'] = $row['colA']  + 99;
            $row['as'] = $row['colA']  + 99;
            return $row;
        });
        self::$driver->setUseFieldNames(false);

        $imported_lines = self::$importer->run();

        self::$importer->setCallbackRow(null);

        $this->assertEquals(2, $imported_lines);
        $value = self::$pdo->query("SELECT colA FROM target_table")->fetchColumn();
        $this->assertEquals(100, $value);
    }

    public function testNewFieldNames()
    {
        self::$driver->setInsertModeAdvanced();
        self::$driver->setUseFieldNames(true);
        self::$pdo->query("ALTER TABLE target_table ADD COLUMN colB INT UNSIGNED");

        self::$importer->setCallbackRow( function($row) {
            $row['colA'] = 1;
            $row['colB'] = 2;
            return $row;
        });
        $imported_lines = self::$importer->run();

        $this->assertEquals(2, $imported_lines);
        self::$importer->setCallbackRow(null);


    }

    public function testConcurrentInsert()
    {
        self::$driver->setInsertModeAdvanced();
        self::$driver->concurrent_insert = true;
        $imported_lines = self::$importer->run();
        $this->assertEquals(2, $imported_lines);
    }

    public function testTransactionStrategy()
    {
        self::$driver->setInsertModeAdvanced();
        $transactionStrategy = new \Jinraynor1\TableImporter\Strategies\TransactionTableStrategy();
        self::$importer->setImportStrategy($transactionStrategy);

        $imported_lines = self::$importer->run();
        $this->assertEquals(2, $imported_lines);

    }

    public function testCharacterSet()
    {
        self::$driver->setInsertModeAdvanced();
        self::$driver->character_set = 'utf8';


        $imported_lines = self::$importer->run();
        $this->assertEquals(2, $imported_lines);

    }

    public function testInvalidCharacterSet()
    {
        self::$driver->setInsertModeAdvanced();
        self::$driver->character_set = 'invalid_charset';
        $imported_lines = self::$importer->run();
        $this->assertEquals(0, $imported_lines);
    }

    public function testThrowException()
    {
        self::$pdo->query("DROP TABLE target_table");

        self::$importer->setThrowExceptions(true);
        try {
            self::$importer->run();

            throw new Exception("Must not get here");
        } catch (Exception $e) {
            $this->assertTrue(true);
        }


    }
}