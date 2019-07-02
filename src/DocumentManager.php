<?php 

namespace Lungo\Doc;

use Lungo\Doc\Document;
use Illuminate\Support\Facades\Config;
use Lungo\Doc\Exceptions\CantCreateLineException;
use Lungo\Doc\Exceptions\RulesNotFoundException;

class DocumentManager
{
    protected $documentF, $documentI, $foreignKey;

    public function __construct(Document $documentI, Document $documentF, $foreignKey)
    {
        $this->documentI = $documentI;
        $this->documentF = $documentF;
        $this->foreignKey = $foreignKey;
    }

    public function createFrom ($header, $lines, $rules) {
        if (is_string($rules)) {
            $rules = Config::get('documents.'.$rules, null);//get rules from config file
        }

        if (!$rules) {
            throw new RulesNotFoundException("Error to try access $rules config or doesnt pass any rule");
        }

        $fk = $this->foreignKey;
        
        foreach ($lines as $key => $line) {
            $actualLineId = isset($line[$fk]) ? $line[$fk] : null;//get actual id from line if foreign key if is defined
            if ($actualLineId) {
                $actualLine = $this->documentI->getModel($actualLineId, false);
            }

            if (isset($actualLine) && $actualLine) {//if exist line, then map columns from config
                if ($this->canCreateLine($actualLine, $lines[$key])) {
                    foreach ($rules as $columnFrom => $columnTo) {
                        $lines[$key][$columnTo] = $actualLine->$columnFrom;
                    }
                } else {
                    throw new CantCreateLineException("Line with foreign ('$fk' = $actualLineId) cant be created");
                }
            }
        }

        return $this->documentF->create($header, $lines);
    }

    protected function canCreateLine($lineI, $lineF)
    {
        $statusColumnI = $this->documentI->getLineColumnStatus();
        $openStatusI = $this->documentI->getLineOpenStatus();
        $partiallyCloseStatusI = $this->documentI->getLinePartiallyCloseStatus();
        $quantityColumnI = $this->documentI->getLineQuantityColumn();
        $closeStatusI = $this->documentI->getLineCloseStatus();

        $statusColumnF = $this->documentF->getLineColumnStatus();
        $quantityColumnF = $this->documentF->getLineQuantityColumn();
        $closeStatusF = $this->documentF->getLineCloseStatus();

        $quantity = $lineI->$quantityColumnI - $this->documentF->getModelLinesByFK($lineI->id, $this->foreignKey)->where($statusColumnF, '<>', $closeStatusF)->sum($quantityColumnF);

        if ($lineI->$statusColumnI == $openStatusI || $lineI->$statusColumnI == $partiallyCloseStatusI) {
            
            //check quantity line
            if ($lineF[$quantityColumnF] < $quantity) {
                $lineI->$statusColumnI = $partiallyCloseStatusI;
                $lineI->save();
            } elseif ($lineF[$quantityColumnF] == $quantity) {
                $lineI->$statusColumnF = $closeStatusI;
                $lineI->save();
            } else {
                return false;
            }

            return true;
        } else {
            return false;
        }
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
                $line2Qty = $this->documentF->getModelLinesByFK($lineId, $this->foreignKey)->get()->sum($this->documentF->getLineQuantityColumn());

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
