<?php


namespace Jinraynor1\TableImporter\Drivers;


class MySQL extends AbstractDatabase
{

    public $local_in_file = true;
    public $concurrent_insert = false;

    public function optimizedInsert()
    {

        list($delimiter, $enclosure,) = $this->file->getCsvControl();

        $local = $this->local_in_file ? "LOCAL" : "";
        $concurrent = $this->concurrent_insert ? "CONCURRENT" : "";

        if($this->use_field_names) {
            $fields = '(`' . implode('`,`', $this->fields) . '`)';
        }else{
            $fields = null;
        }


        $sql = '
			LOAD DATA ' . $concurrent . ' ' . $local . ' INFILE ' . $this->database->quote($this->file->getRealPath()) . '			
			IGNORE INTO TABLE ' . $this->quoteIdent($this->table_name) . '
			CHARACTER SET BINARY
			FIELDS TERMINATED BY ' . $this->database->quote($delimiter) . '
			OPTIONALLY ENCLOSED BY ' . $this->database->quote($enclosure) . '
			ESCAPED BY ' . $this->database->quote($this->csv_escape_char).'
			' . $fields;


        $this->sth = $this->database->prepare($sql);
        $this->sth->execute();

        return $this->sth->rowCount();

    }

    private function quoteIdent($field)
    {
        return "`" . str_replace("`", "``", $field) . "`";
    }
}