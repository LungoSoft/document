<?php

namespace Lungo\Doc\Test;

use PHPUnit\Framework\TestCase;
use Mockery;
use Lungo\Doc\Test\Testeable\DocTest;
use Lungo\Doc\Test\Testeable\HeaderModel;
use Lungo\Doc\Test\Testeable\LineModel;

class DocumentTest extends TestCase
{
    public function test_create_document()
    {
        $header = ['name' => 'Luis'];
        $lines = [
            ['name' => 'Antonio'],
            ['name' => 'Guillen'],
        ];
        $doc = new DocTest();

        $doc->create($header, $lines, 'foreign_key');

        $this->assertArrayHasKey('id', $header);
        $this->assertArrayHasKey('id', $lines[0]);
        $this->assertArrayHasKey('id', $lines[1]);
    }

    public function test_edit_header_document()
    {
        $doc = new DocTest();

        //lines and header open - true
        HeaderModel::$status = 'Abierto';
        HeaderModel::createLines('Abierto');
        $result1 = $doc->editHeader(1, ['name' => 'Luis']);

        //one line closed - false
        HeaderModel::$status = 'Abierto';
        HeaderModel::createLines('Cerrado');
        $result2 = $doc->editHeader(1, ['name' => 'Luis']);

        //header closed - false
        HeaderModel::$status = 'Cerrado';
        HeaderModel::createLines('Abierto');
        $result3 = $doc->editHeader(1, ['name' => 'Luis']);

        $this->assertTrue($result1);
        $this->assertFalse($result2);
        $this->assertFalse($result3);
    }

    public function test_edit_line_document()
    {
        $doc = new DocTest();

        //lines and header open - true
        HeaderModel::$status = 'Abierto';
        HeaderModel::createLines('Abierto');
        $result1 = $doc->editLine(1, 2, ['name' => 'Luis']);

        //one line closed - false
        HeaderModel::$status = 'Abierto';
        HeaderModel::createLines('Cerrado');
        $result2 = $doc->editLine(1, 2, ['name' => 'Luis']);

        //header closed - false
        HeaderModel::$status = 'Cerrado';
        HeaderModel::createLines('Abierto');
        $result3 = $doc->editLine(1, 2, ['name' => 'Luis']);

        $this->assertTrue($result1);
        $this->assertFalse($result2);
        $this->assertFalse($result3);
    }

    public function test_destroy_header_document()
    {
        $doc = new DocTest();

        //lines and header open - true
        HeaderModel::$status = 'Abierto';
        HeaderModel::createLines('Abierto');
        $result1 = $doc->destroyHeader(1);

        //one line closed - false
        HeaderModel::$status = 'Abierto';
        HeaderModel::createLines('Cerrado');
        $result2 = $doc->destroyHeader(1);

        //header closed - false
        HeaderModel::$status = 'Cerrado';
        HeaderModel::createLines('Abierto');
        $result3 = $doc->destroyHeader(1);

        $this->assertTrue($result1);
        $this->assertFalse($result2);
        $this->assertFalse($result3);
    }

    public function test_destroy_line_document()
    {
        $doc = new DocTest();

        //lines and header open - true
        HeaderModel::$status = 'Abierto';
        HeaderModel::createLines('Abierto');
        $result1 = $doc->destroyHeader(1, 2);

        //one line closed - false
        HeaderModel::createLines('Cerrado');
        HeaderModel::$status = 'Abierto';
        $result2 = $doc->destroyHeader(1, 2);

        //header closed - false
        HeaderModel::$status = 'Cerrado';
        HeaderModel::createLines('Abierto');
        $result3 = $doc->destroyHeader(1, 2);

        $this->assertTrue($result1);
        $this->assertFalse($result2);
        $this->assertFalse($result3);
    }
}
