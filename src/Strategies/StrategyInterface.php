<?php


namespace Jinraynor1\TableImporter\Strategies;
use Jinraynor1\TableImporter\Drivers\DatabaseInterface;


interface StrategyInterface
{
    public function replace(DatabaseInterface $import_driver, \PDO $db_target, $table_name);

}