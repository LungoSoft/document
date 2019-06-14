<?php 

namespace Lungo\Doc\Test\Testeable;

class Model extends \stdClass 
{
    public function update ($data) 
    {
        $std1 = new \stdClass;
        $std1->id = 150;
        return $std1;
    }

    public function save () 
    {
        return true;
    }
}
