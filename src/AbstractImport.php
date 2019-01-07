<?php

namespace Jinraynor1\TableImporter;


use Jinraynor1\DbWrapper\PdoWrapper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;


abstract class AbstractImport implements ImportInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $tmp_file;
    protected $table_name;
    protected $tmp_filename_prefix = 'jinraynor1_table_importer_';

    const MODE_REPLACE = 1;
    const MODE_APPEND = 2;

    private $import_options;


    /**
     * @var PdoWrapper
     */
    protected $db_source;
    /**
     * @var PdoWrapper
     */
    protected $db_target;



    /**
     * AbstractRemoteImport constructor.
     */
    public function __construct($import_options = self::MODE_REPLACE)
    {
        $this->import_options = $import_options;

        $this->logger = new NullLogger();
    }

    public function __destruct()
    {

        if ($this->tmp_file && is_file($this->tmp_file))
            @unlink($this->tmp_file);

    }

    public function initDatabaseConnections()
    {
        $this->db_source = $this->getSourceDatabaseConnection();
        $this->db_target = $this->getTargetDatabaseConnection();
    }


    /**
     * @return int|mixed
     */
    public function pullData()
    {

        $this->db_source->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $stmt = $this->db_source->prepare(
            $this->getSqlPullData()
        );
        $stmt->execute();


        $handler = fopen($this->tmp_file, 'w');
        $records_founded = 0;
        while ($row = $stmt->fetch()) {

            foreach ($row as $field => $value) {
                if ($value === null) {
                    $row[$field] = "\\N";
                }
            }
            $records_founded++;
            fputcsv($handler, $row, "\t");
        }

        fclose($handler);

        return $records_founded;

    }

    /**
     * @param $filename
     * @param $table_name
     * @return int
     */
    protected function loadTable($filename, $table_name)
    {

        $sth = $this->db_target->prepare(
            "LOAD DATA LOCAL INFILE '$filename' INTO TABLE $table_name FIELDS OPTIONALLY ENCLOSED BY '\"'"
        );
        $sth->execute();

        return $sth->rowCount();


    }

    /**
     * @return bool|int|mixed
     */
    public function pushData()
    {

        if ($this->import_options == self::MODE_APPEND) {

            $affected_rows = $this->loadTable($this->tmp_file, $this->table_name);

        } elseif ($this->import_options == self::MODE_REPLACE) {

            $old_table = $this->table_name;
            $tmp_table = "tmp_$this->table_name";
            $new_table = "new_{$this->table_name}";

            $this->db_target->exec("DROP TABLE IF EXISTS $tmp_table");
            $this->db_target->exec("DROP TABLE IF EXISTS $new_table");

            $this->db_target->exec("CREATE TABLE $new_table LIKE $old_table");

            $affected_rows = $this->loadTable($this->tmp_file, $new_table);


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

    abstract function beforeImport();

    abstract function afterImport();


}