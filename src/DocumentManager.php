<?php 

namespace Lungo\Doc;

use Lungo\Doc\Document;

class DocumentManager
{
    protected $documentF, $documentI, $foreignKey;

    public function __construct(Document $documentI, Document $documentF, $foreignKey)
    {
        $this->documentI = $documentI;
        $this->documentF = $documentF;
        $this->foreignKey = $foreignKey;
    }
    
    public function editQuantityLine($id, $lineId, $quantity) 
    {
        $line = null;
        $result = $this->documentI->getModels($id, $lineId, function ($headerM, $lineM) use (&$line) {
            $line = $lineM;
        });

        if ($result) {
            $this->editQuantity($line, $quantity);
            return true;
        } else {
            $header = $this->documentI->getModel($id);
            $headerColumn = $this->documentI->getHeaderColumnStatus();
            $headerStatus = $this->documentI->getHeaderOpenStatus();

            $line = $this->documentI->getModel($lineId, false);
            $lineColumn = $this->documentI->getLineColumnStatus();
            $lineStatus = $this->documentI->getLinePartiallyCloseStatus();
            $lineQuantityColumn = $this->documentI->getLineQuantityColumn();
            
            if ($header->$headerColumn == $headerStatus && $line->$lineColumn == $lineStatus) {
                $line2Qty = $this->documentF->getModelLinesByFK($lineId, $this->foreignKey)->sum($this->documentF->getLineQuantityColumn());

                if ($quantity > $line2Qty) {
                    $this->editQuantity($line, $quantity);
                    return true;
                } elseif ($quantity == $line2Qty) {
                    $line->$lineQuantityColumn = $quantity;
                    $line->$lineColumn = $lineStatus;
                    $line->save();
                    return true;
                }
            }

            return false;
        }
    }

    protected function editQuantity($line, $quantity)
    {
        $column = $this->documentI->getLineQuantityColumn();
        $line->$column = $quantity;
        $line->save();
    }
}
