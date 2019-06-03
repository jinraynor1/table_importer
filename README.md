# table_importer
Imports data from remote source in a generic way
## Getting Started

Brief example of how to use
```php
<?php
$date = new DateTime("2019-02-01");
$regex = new \Jinraynor1\TableCleaner\TableRegex("/^dropme([0-9]{8})$/", $date, "Ymd");
$driver = new \Jinraynor1\TableCleaner\Drivers\Sqlite(new PDO('mysql:host=localhost;dbname=testdb','root',''));

$table_cleaner = new \Jinraynor1\TableCleaner\TableCleaner($driver, $regex);
$table_cleaner->drop();
```        
Please see tests directory for more example on how to use this library
