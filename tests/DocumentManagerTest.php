<?php 

namespace Lungo\Doc\Test;

use Mockery;
use Lungo\Doc\Test\Testeable\Model;
use Lungo\Doc\DocumentManager;
use PHPUnit\Framework\TestCase;

class DocumentManagerTest extends TestCase
{
    public function test_edit_quantity_line () 
    {
        $mockDoc1 = Mockery::mock(\Lungo\Doc\Test\Testeable\DocTest::class);
        $mockDoc2 = Mockery::mock(\Lungo\Doc\Test\Testeable\DocTest::class);

        $docModel1 = new Model;
        $docModel11 = new Model;
        $docModel2 = new Model;
        $docModel21 = new Model;

        $docModel1->status = 'Abierto';
        $docModel1->quantity = 5;
        $docModel11->status = 'Parcialmente Abierto';
        $docModel11->quantity = 5;
        $docModel2->quantity = 2;
        $docModel21->quantity = 1;

        $mockDoc1->shouldReceive('getModels')->andReturn(false);
        $mockDoc1->shouldReceive('getModel')->withArgs([1])->andReturn($docModel1);
        $mockDoc1->shouldReceive('getModel')->withArgs([2, false])->andReturn($docModel11);
        $mockDoc1->shouldReceive('getHeaderColumnStatus')->andReturn('status');
        $mockDoc1->shouldReceive('getHeaderOpenStatus')->andReturn('Abierto');
        $mockDoc1->shouldReceive('getLineColumnStatus')->andReturn('status');
        $mockDoc1->shouldReceive('getLinePartiallyCloseStatus')->andReturn('Parcialmente Abierto');
        $mockDoc1->shouldReceive('getLineQuantityColumn')->andReturn('quantity');

        $mockDoc2->shouldReceive('getModelLinesByFK')->andReturn(collect([$docModel2, $docModel21]));
        $mockDoc2->shouldReceive('getLineColumnStatus')->andReturn('status');
        $mockDoc2->shouldReceive('getLineCloseStatus')->andReturn('Cerrado');
        $mockDoc2->shouldReceive('getLineQuantityColumn')->andReturn('quantity');

        $doc = new DocumentManager($mockDoc1, $mockDoc2, "foreign_key");
        $result1 = $doc->editQuantityLine(1, 2, 4);
        $result2 = $doc->editQuantityLine(1, 2, 3);
        $result3 = $doc->editQuantityLine(1, 2, 2);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertFalse($result3);
    }

    public function test_prepare_document_lines ()
    {
        $mockDoc1 = Mockery::mock(\Lungo\Doc\Test\Testeable\DocTest::class);
        $mockDoc2 = Mockery::mock(\Lungo\Doc\Test\Testeable\DocTest::class);

        $docModel = new Model;
        $docModel->product_name = 'Paper';
        $docModel->price = 100;
        $docModel->tax = 16;

        $mockDoc1->shouldReceive('getModel')->withArgs([2, false])->andReturn($docModel);

        $lines = [
            [
                'product' => 'Product Name',
                'price' => 10,
                'tax_price' => 1.6,
            ],
            [
                'foreign_key' => 2,
            ],
        ];
        $rules = [
            'product_name' => 'product',
            'price' => 'price',
            'tax' => 'tax_price',
        ];

        $doc = new DocumentManager($mockDoc1, $mockDoc2, "foreign_key");
        $result = $doc->createFrom($lines, $rules);

        $this->assertArraySubset($result, [[
                'product' => 'Product Name',
                'price' => 10,
                'tax_price' => 1.6,
            ],
            [
                'foreign_key' => 2,
                'product' => 'Paper',
                'price' => 100,
                'tax_price' => 16,
            ]
        ]);
    }
}
