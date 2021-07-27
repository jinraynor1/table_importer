<?php


namespace Jinraynor1\TableImporter\Strategies;


use Jinraynor1\TableImporter\Drivers\DatabaseInterface;

class TransactionTableStrategy implements StrategyInterface
{
    public function load(DatabaseInterface $import_driver, \PDO $db_target, $table_name)
    {
        $db_target->beginTransaction();
        $affected_rows = $import_driver->load();
        $db_target->commit();
        return $affected_rows;

    }

    public function replace(DatabaseInterface $import_driver, \PDO $db_target, $table_name)
    {
        $db_target->beginTransaction();

        $db_target->exec("DELETE FROM $table_name");


        $affected_rows = $import_driver->load();

        $db_target->commit();

        return $affected_rows;
    }

}