<?php


namespace Jinraynor1\TableImporter;


use Jinraynor1\DbWrapper\PdoWrapper;

trait ImportTrait
{
    public function run()
    {

        $this->logger->info("start");

        try {


            $this->initDatabaseConnections();

            $this->table_name = $this->getTableName();

            if (!$this->table_name)
                throw new \InvalidArgumentException("invalid table name:$this->table_name");


            $this->beforeImport();

            $this->tmp_file = tempnam(sys_get_temp_dir(), $this->tmp_filename_prefix);

            $records_founded = $this->pullData();
            $this->logger->info(sprintf("founded %d records on remote database", $records_founded));


            $records_imported = $this->pushData();
            $this->logger->info(sprintf("%d records imported", $records_imported));


            $this->afterImport();

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        $this->logger->info("end");

    }

    public function beforeImport()
    {
    }

    public function afterImport()
    {
    }


    /**
     * @param $config
     * @return PdoWrapper
     */
    public function openDB(ConfigDatabaseInterface $config)
    {


        $dsn = sprintf("%s:dbname=%s;host=%s;port=%s",
            $config->getDriver(), $config->getDatabaseName(),
            $config->getHost(), $config->getPort());


        $pdo = new  \PDO($dsn, $config->getUsername(), $config->getPassword(), array(
            \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC

        ));

        //it seems this not works in constructors for some php versions...
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;

    }
}