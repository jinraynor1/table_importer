<?php


namespace Jinraynor1\TableImporter\Drivers;


class PostgreSQL extends AbstractDatabase
{

    public function __construct()
    {

    }

    public function optimizedInsert()
    {

        $sql = "COPY myTable FROM '/path/to/file/on/server' ( FORMAT CSV, DELIMITER('|') )";
    }

}