<?php


namespace Jinraynor1\TableImporter\Strategies;


use Jinraynor1\TableImporter\Drivers\DatabaseInterface;
use PDO;

class RenameTableStrategy implements StrategyInterface
{
    public function load(DatabaseInterface $import_driver, \PDO $db_target, $table_name)
    {
        return $import_driver->load();

    }

    public function replace(DatabaseInterface $import_driver, PDO $db_target, $table_name)
    {

        $old_table = $table_name;
        $tmp_table = "tmp_$table_name";
        $new_table = "new_{$table_name}";

        $db_target->exec("DROP TABLE IF EXISTS $tmp_table");
        $db_target->exec("DROP TABLE IF EXISTS $new_table");

        $db_target->exec("CREATE TABLE $new_table LIKE $old_table");

        $import_driver->setTableName($new_table);

        $affected_rows = $import_driver->load();


        $db_target->exec(
            "RENAME TABLE $old_table TO $tmp_table,
                 $new_table TO $old_table,
                 $tmp_table TO $new_table"
        );

        $db_target->exec("DROP TABLE IF EXISTS $tmp_table");
        $db_target->exec("DROP TABLE IF EXISTS $new_table");

        return $affected_rows;

    }


}