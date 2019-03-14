<?php


namespace Jinraynor1\TableImporter\Drivers;


class MySQL extends AbstractDatabase
{


    public function optimizedInsert()
    {

        list($delimiter, $enclosure,) = $this->file->getCsvControl();

        $sql = '
			LOAD DATA LOCAL INFILE ' . $this->database->quote($this->file->getRealPath()) . '
			REPLACE INTO TABLE ' . $this->quoteIdent('new_' . $this->table_name) . '
			FIELDS TERMINATED BY ' . $this->database->quote($delimiter) . '
			OPTIONALLY ENCLOSED BY ' . $this->database->quote($enclosure) . '
			ESCAPED BY ' . $this->database->quote($this->csv_escape_char);


        $this->sth = $this->database->prepare($sql);
        $this->sth->execute();

        return $this->sth->rowCount();

    }

    private function quoteIdent($field)
    {
        return "`" . str_replace("`", "``", $field) . "`";
    }
}