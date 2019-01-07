<?php


namespace Jinraynor1\TableImporter;


interface ImportInterface {




    function getSourceDatabaseConnection();

    function getTargetDatabaseConnection();


    /**
     * Logic pulling data
     * @return mixed
     */
    function pullData();

    /**
     * Logic pushing data
     * @return mixed
     */
    function pushData();

    /**
     * Must return the table name where data will be copied
     * @return mixed
     */
    function getTableName();
    /**
     * Must return Sql query for getting the actual data to import
     * @return mixed
     */
    function getSqlPullData();


}