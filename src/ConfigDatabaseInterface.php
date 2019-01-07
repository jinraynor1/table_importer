<?php


namespace Jinraynor1\TableImporter;


interface ConfigDatabaseInterface
{
    function getHost();

    function getDatabaseName();

    function getUsername();

    function getPassword();

    function getPort();

    function getDriver();


}