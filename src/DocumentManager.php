<?php 

namespace Lungo\Doc;

use Lungo\Doc\Document;
use Illuminate\Support\Facades\Config;

class DocumentManager
{
    protected $documentF, $documentI, $foreignKey;

    public function __construct(Document $documentI, Document $documentF, $foreignKey)
    {
        $this->documentI = $documentI;
        $this->documentF = $documentF;
        $this->foreignKey = $foreignKey;
    }

    public function createFrom ($lines, $rules) {
        if (is_string($rules)) {
            $rules = Config::get('documents.'.$rules, null);//get rules from config file
        }

        if (!$rules) {
            throw new \Exception("Error to try access $rules config or doesnt pass any rule");
        }

        $documentI = $this->documentI;//get instance of source document
        $fk = $this->foreignKey;
        
        foreach ($lines as $key => $line) {
            $actualLineId = isset($line[$fk]) ? $line[$fk] : null;//get actual id from line if foreign key is defined
            if ($actualLineId) {
                $actualLine = $documentI->getModel($actualLineId, false);
            }

            if (isset($actualLine) && $actualLine) {//if exist line, then map columns from config
                foreach ($rules as $columnFrom => $columnTo) {
                    $lines[$key][$columnTo] = $actualLine->$columnFrom;
                }
            }
        }

        return $lines;
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
