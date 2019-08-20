<?php

namespace Jinraynor1\TableImporter;


use Jinraynor1\TableImporter\Drivers\AbstractDatabase;
use Jinraynor1\TableImporter\Drivers\DatabaseInterface;
use Jinraynor1\TableImporter\Drivers\DefaultDriver;
use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;


class Import
{


    const MODE_REPLACE = 1;
    const MODE_APPEND = 2;
    private $import_mode = self::MODE_REPLACE;
    protected $tmp_file;
    protected $table_name;
    protected $tmp_filename_prefix = 'jinraynor1_table_importer_';
    protected $csv_delimiter = ',';
    protected $csv_enclosure = '"';
    protected $csv_escape_char = '\\';
    protected $csv_null_value = '\\N';

    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * @var \SplFileObject
     */
    protected $file;

    /**
     * @var \PDO
     */
    protected $db_source;
    /**
     * @var \PDO
     */
    protected $db_target;

    /**
     * @var AbstractDatabase
     */
    protected $import_driver;
    /**
     * @var ConfigDatabase
     */
    private $source_config;
    /**
     * @var ConfigDatabase
     */
    private $target_config;

    /**
     * @var string
     */
    private $query;


    /**
     * @var null
     */
    private $callback_before_push_data = null;

    /**
     * AbstractRemoteImport constructor.
     */
    public function __construct(ConfigDatabase $source_config, ConfigDatabase $target_config)
    {

        $this->logger = new NullLogger();
        $this->import_driver = new DefaultDriver();

        $this->source_config = $source_config;
        $this->target_config = $target_config;
    }

    /**
     * Create from an existing connection
     * @param \PDO $db_source
     * @param \PDO $db_target
     * @return Import
     */
    public static function createFromConnection(\PDO $db_source, \PDO $db_target)
    {
        $obj = new self(new ConfigDatabase(), new ConfigDatabase());
        $obj->db_source = $db_source;
        $obj->db_target = $db_target;

        $obj->setDefaultOptions($obj->db_source);
        $obj->setDefaultOptions($obj->db_target);
        return $obj;

    }


    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function setImportModeIsAppend()
    {
        $this->import_mode = self::MODE_APPEND;
        return $this;
    }

    public function setImportModeIsReplace()
    {
        $this->import_mode = self::MODE_REPLACE;
        return $this;
    }

    public function setTableName($table_name)
    {
        $this->table_name = $table_name;
        return $this;
    }

    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    public function setCallbackBeforePushData($callback_before_push_data)
    {
        $this->callback_before_push_data = $callback_before_push_data;
        return $this;
    }

    public function setImportDriver(DatabaseInterface $import_driver)
    {
        $this->import_driver = $import_driver;
        return $this;
    }

    public function __destruct()
    {
        if ($this->file && $this->file->isFile()) {
            @unlink($this->file->getRealPath());
        }
    }

    public function run()
    {

        $this->logger->info("start");
        $records_imported = 0;
        try {

            $this->validate();

            if (!$this->db_source) {
                $this->db_source = $this->openDB($this->source_config);
            }

            if(!$this->db_target){
                $this->db_target = $this->openDB($this->target_config);
            }

            $this->buildFileObject();


            $this->import_driver->setDatabase($this->db_target);
            $this->import_driver->setFile($this->file);
            $this->import_driver->setTableName($this->table_name);
            $this->import_driver->setCsvNullValue($this->csv_null_value);
            $this->import_driver->setCsvEscapeChar($this->csv_escape_char);

            $records_founded = $this->pullData();
            $this->logger->info(sprintf("founded %d records on remote database", $records_founded));
            $this->import_driver->setRecordsFounded($records_founded);

            if ($this->callback_before_push_data && is_callable($this->callback_before_push_data)) {
                call_user_func_array($this->callback_before_push_data, array($this->file));
            }


            $records_imported = $this->pushData();
            $this->logger->info(sprintf("%d records imported", $records_imported));


        } catch (\Exception $e) {
            $this->logger->critical(sprintf("error code: %s, error message: %s", $e->getLine(), $e->getMessage()));
            return $records_imported;
        }

        $this->logger->info("end");

        return $records_imported;
    }

    public function validate()
    {
        if (!$this->table_name)
            throw new \InvalidArgumentException("you should set a table name");

        if (!$this->query)
            throw new \InvalidArgumentException("you should set a query");
    }


    protected function buildFileObject()
    {
        $tmp_filename = tempnam(sys_get_temp_dir(), $this->tmp_filename_prefix);
        $this->file = new \SplFileObject($tmp_filename, 'w+');
        $this->file->setCsvControl($this->csv_delimiter, $this->csv_enclosure, $this->csv_escape_char);
        $this->file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);

    }


    /**
     * @param $config
     * @return \PDO
     */
    public function openDB(ConfigDatabaseInterface $config)
    {

        $config->getDriver();
        $options = array();
        if ($config->getDatabaseName()) {
            $options[] = "dbname=" . $config->getDatabaseName();
        }

        if ($config->getHost()) {
            $options[] = "host=" . $config->getHost();
        }

        if ($config->getPort()) {
            $options[] = "port=" . $config->getPort();
        }

        if ($config->getCharset()) {
            $options[] = "charset=" . $config->getCharset();
        }

        $dsn = $config->getDriver() . ':' . implode(";", $options);

        $pdo = new  \PDO($dsn, $config->getUsername(), $config->getPassword(), array(
            \PDO::MYSQL_ATTR_LOCAL_INFILE => true,

        ));

        $this->setDefaultOptions($pdo);

        return $pdo;

    }

    private function setDefaultOptions(\PDO $db)
    {
        $options = array(
            array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION),
            array(\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC)

        );
        foreach ($options as $option) {
            foreach ($option as $k => $v) {
                $db->setAttribute($k, $v);
            }
        }
    }


    /**
     * @return int|mixed
     */
    public function pullData()
    {

        $this->db_source->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $stmt = $this->db_source->prepare(
            $this->query
        );
        $stmt->execute();


        $records_founded = 0;

        $handler = fopen($this->file->getRealPath(), "w");
        while ($row = $stmt->fetch()) {

            foreach ($row as $field => $value) {
                if ($value === null) {
                    $row[$field] = $this->csv_null_value;
                }
            }
            $records_founded++;

            fputcsv($handler, $row);

        }

        fclose($handler);


        return $records_founded;

    }


    /**
     * @return bool|int|mixed
     */
    public function pushData()
    {


        if ($this->import_mode == self::MODE_APPEND) {
            $this->logger->info("push mode is append");

            $affected_rows = $this->import_driver->load();

        } elseif ($this->import_mode == self::MODE_REPLACE) {
            $this->logger->info("push mode is replace");

            $old_table = $this->table_name;
            $tmp_table = "tmp_$this->table_name";
            $new_table = "new_{$this->table_name}";

            $this->db_target->exec("DROP TABLE IF EXISTS $tmp_table");
            $this->db_target->exec("DROP TABLE IF EXISTS $new_table");

            $this->db_target->exec("CREATE TABLE $new_table LIKE $old_table");

            $this->import_driver->setTableName($new_table);

            $affected_rows = $this->import_driver->load();


            $this->db_target->exec(
                "RENAME TABLE $old_table TO $tmp_table,
                 $new_table TO $old_table,
                 $tmp_table TO $new_table"
            );

            $this->db_target->exec("DROP TABLE IF EXISTS $tmp_table");
            $this->db_target->exec("DROP TABLE IF EXISTS $new_table");
        } else {

            throw new \InvalidArgumentException("Invalid import mode");
        }

        return $affected_rows;


    }


}