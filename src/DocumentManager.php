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
                $line2 = $this->documentF->getModelLineByFK($id, $lineId, $this->foreignKey);
                $lineColumn2 = $this->documentF->getLineColumnStatus();
                $lineStatus2 = $this->documentF->getLineCloseStatus();
                $lineQuantityColumn2 = $this->documentF->getLineQuantityColumn();

                if ($quantity > $line2->$lineQuantityColumn2) {
                    $this->editQuantity($line, $quantity);
                    return true;
                } elseif ($quantity == $line2->$lineQuantityColumn2) {
                    $line->$lineQuantityColumn = $quantity;
                    $line->$lineColumn2 = $lineStatus2;
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
