<?php


namespace Jinraynor1\TableImporter\Drivers;


class DefaultDriver extends AbstractDatabase
{

    public function optimizedInsert()
    {
        throw new \RuntimeException("Default Driver does not know how to use optimized insert");
    }

}