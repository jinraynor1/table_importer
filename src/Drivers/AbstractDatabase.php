<?php


namespace Jinraynor1\TableImporter\Drivers;


abstract class AbstractDatabase implements DatabaseInterface
{

    const USE_NORMAL_INSERT = 1;
    const USE_MULTIPLE_INSERT = 2;
    const USE_ADVANCED_LOAD = 4;

    private $insert_mode = self::USE_NORMAL_INSERT;
    /**
     * @var \SplFileObject
     */
    protected $file;

    /**
     * @var \PDO
     */
    protected $database;
    /**
     * @var string
     */
    protected $table_name;
    /**
     * @var array
     */
    private $placeholders_list;
    /**
     * @var \PDOStatement
     */
    protected $sth;

    /**
     * @var array
     */
    private $buffer = array();

    private $lines_chunk = 10;


    protected $csv_null_value = null;


    protected $csv_escape_char = null;

    private $records_founded = 0;

    /**
     * @param \SplFileObject $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @param \PDO $database
     */
    public function setDatabase($database)
    {
        $this->database = $database;
    }

    /**
     * @param string $table_name
     */
    public function setTableName($table_name)
    {
        $this->table_name = $table_name;
    }

    public function setInsertModeBasic()
    {
        $this->insert_mode = self::USE_NORMAL_INSERT;
        return $this;
    }

    public function setInsertModeMultiple()
    {
        $this->insert_mode = self::USE_MULTIPLE_INSERT;
        return $this;
    }


    public function setInsertModeAdvanced()
    {
        $this->insert_mode = self::USE_ADVANCED_LOAD;
        return $this;
    }

    /**
     * @param null $csv_null_value
     */
    public function setCsvNullValue($csv_null_value)
    {
        $this->csv_null_value = $csv_null_value;
        return $this;
    }

    public function setCsvEscapeChar($csv_escape_char)
    {
        $this->csv_escape_char = $csv_escape_char;
        return $this;
    }

    /**
     * @param int $records_founded
     */
    public function setRecordsFounded($records_founded)
    {
        $this->records_founded = $records_founded;
        return $this;
    }

    public function load()
    {
        if ($this->insert_mode == self::USE_ADVANCED_LOAD) {

            return $this->optimizedInsert();

        } elseif ($this->insert_mode == self::USE_MULTIPLE_INSERT) {

            return $this->multipleInsert();

        } else {

            return $this->normalInsert();

        }


    }

    protected function normalInsert()
    {

        $this->generatePlaceHolders();
        $this->generateStatement(1);
        $lines = 0;
        foreach ($this->file as $line_number => $row) {

            if (!$row) {
                continue;
            }

            foreach ($row as $field_number => &$field) {

                if ($field == $this->csv_null_value) {
                    $field = NULL;
                }

            }

            if ($this->executeImportStatement($row)) {
                $lines++;

            }
        }
        return $lines;
    }

    protected function multipleInsert()
    {

        $this->generatePlaceHolders();
        $this->generateStatement($this->lines_chunk);

        $lines = 0;

        foreach ($this->file as $line_number => $row) {

            if (!$row) {
                continue;
            }

            foreach ($row as $field_number => &$field) {

                if ($field == $this->csv_null_value) {
                    $field = NULL;
                }

            }


            if (!$line_number || $line_number % $this->lines_chunk != 0) {

                $this->buffer = array_merge($this->buffer, $row);

                if ($line_number + 1 == $this->records_founded) {


                    $this->generateStatement($this->records_founded - $lines);
                    if ($this->executeImportStatement($this->buffer)) {
                        $lines += $this->records_founded - $lines;
                    }

                }

            } else {

                if ($this->executeImportStatement($this->buffer)) {
                    $lines += $this->lines_chunk;
                }

                $this->buffer = $row;
            }


        }


        return $lines;


    }


    public function generatePlaceHolders()
    {
        $this->file->rewind();
        $first_line = $this->file->current();
        $this->placeholders_list = implode(',', array_fill(0, count($first_line), '?'));
        $this->file->rewind();
    }

    public function generateStatement($lines_chunk)
    {


        $values_group_list = array();
        for ($i = 0; $i < $lines_chunk; $i++) {
            $values_group_list[] = "($this->placeholders_list)\n";
        }

        $sql = sprintf("INSERT INTO new_$this->table_name VALUES %s", implode(',', $values_group_list));


        $this->sth = $this->database->prepare($sql);
    }

    public function executeImportStatement(array $buffer)
    {
        return $this->sth->execute($buffer);

    }
}