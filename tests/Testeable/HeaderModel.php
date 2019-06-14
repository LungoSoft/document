<?php 

namespace Lungo\Doc\Test\Testeable;

class HeaderModel
{
    public static $status = 'Abierto';
    public static $lines;

    public static function create($data) 
    {
        $std = new Model;
        $std->id = 10;
        return $std;
    }

    public static function find($id)
    {
        $std = new Model;
        $std->id = $id;
        $std->status = static::$status;
        $std->lines = static::$lines;
        return $std;
    }

    public static function createLines($status)
    {
        $std1 = new Model;
        $std2 = new Model;

        $std1->status = 'Abierto';
        $std1->id = 1;
        $std2->status = $status;
        $std2->id = 2;

        static::$lines = [$std1, $std2];
    }
}
