<?php 

namespace Lungo\Doc\Test\Testeable;

class LineModel
{
    public static $status = 'Abierto';
    public static $lines;
    public static $quantity;

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
        $std->quantity = static::$quantity;
        return $std;
    }
}
