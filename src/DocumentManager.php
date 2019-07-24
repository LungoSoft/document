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

    private $modifiedLines = [];

    /**
     * Crea una línea FINAL solo si está abierta o parcialmente abierta todas las líneas y existe la cantidad disponible de acuerdo a los documentos FINALES ya creados con ese id y con estatus abierto.
     */
    public function createFrom ($header, $lines, $rules) {
        if (is_string($rules)) {
            $rules = Config::get('documents.'.$rules, null);//get rules from config file
        }

        if (!$rules) {
            throw new RulesNotFoundException("Error to try access $rules config or doesnt pass any rule");
        }

        $fk = $this->foreignKey;
        $this->modifiedLines = [];
        $toUpdate = [];
        
        foreach ($lines as $key => $line) {
            $actualLineId = isset($line[$fk]) ? $line[$fk] : null;//get actual id from line if foreign key if is defined
            if ($actualLineId) {
                $actualLine = $this->documentI->getModel($actualLineId, false);
                $foreignKeyLine = $this->documentI->getForeignKeyLine();
                if (!collect($toUpdate)->contains($actualLine->$foreignKeyLine)) {
                    $toUpdate[] = $actualLine->$foreignKeyLine;
                }
            }

            if ($actualLineId) {//if exist line, then map columns from config
                if ($this->canCreateLine($actualLine, $lines[$key])) {
                    foreach ($rules as $columnFrom => $columnTo) {
                        $lines[$key][$columnTo] = $actualLine->$columnFrom;
                    }
                } else {
                    throw new CantCreateLineException("Line with foreign ('$fk' = $actualLineId) cant be created");
                }
            }
        }

        $this->updateLinesStatus();
        //edit header status models
        foreach ($toUpdate as $headerId) {
            $actualHeader = $this->documentI->getModel($headerId);
            $this->updateDocumentStatus($actualHeader);
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
        $closeStatusF = $this->documentF->getLineDestroyedStatus();

        $quantity = $lineI->$quantityColumnI - $this->documentF->getModelLinesByFK($lineI->id, $this->foreignKey)->where($statusColumnF, '<>', $closeStatusF)->sum($quantityColumnF);

        if ($lineI->$statusColumnI == $openStatusI || $lineI->$statusColumnI == $partiallyCloseStatusI) {
            
            //check quantity line
            if ($lineF[$quantityColumnF] < $quantity) {
                $lineI->$statusColumnI = $partiallyCloseStatusI;
                $this->modifiedLines[] = $lineI;
            } elseif ($lineF[$quantityColumnF] == $quantity) {
                $lineI->$statusColumnF = $closeStatusI;
                $this->modifiedLines[] = $lineI;
            } else {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }
    
    private function updateLinesStatus() {
        foreach ($this->modifiedLines as $lineI) {
            $lineI->save();
        }
    }
    
    public function editQuantityLine($id, $lineId, $quantity) 
    {
        $lineF = null;
        $result = $this->documentF->getModels($id, $lineId, function ($headerM, $lineM) use (&$lineF) {
            $lineF = $lineM;
        });

        if ($result) {
            $lineQuantityColumnF = $this->documentF->getLineQuantityColumn();
            
            //get line of initial document
            $fk = $this->foreignKey;
            $lineI = $this->documentI->getModel($lineF->$fk, false);
            $lineQuantityColumnI = $this->documentI->getLineQuantityColumn();
            $lineColumnI = $this->documentI->getLineColumnStatus();

            if ($lineF->$lineQuantityColumnF == $quantity) {
                return true;
            } elseif ($lineI->$lineQuantityColumnI == $quantity) {//cerrada
                $lineI->$lineColumnI = $this->documentI->getLineCloseStatus();
            } elseif ($lineI->$lineQuantityColumnI > $quantity) {//parcialmente cerrada
                $lineI->$lineColumnI = $this->documentI->getLinePartiallyCloseStatus();
            } elseif ($lineI->$lineQuantityColumnI < $quantity) {//cerrada
                return false;
            }

            $lineF->$lineQuantityColumnF = $quantity;
            $lineF->save();
            $lineI->save();

            //edit header status model
            $foreignKeyLine = $this->documentI->getForeignKeyLine();
            $this->updateDocumentStatus($this->documentI->getModel($lineI->$foreignKeyLine));

            return true;
        } else {
            return false;
        }
    }

    public function removeLine($id, $lineId)
    {
        $lineF = null;
        $result = $this->documentF->getModels($id, $lineId, function ($headerM, $lineM) use (&$lineF) {
            $lineF = $lineM;
        });

        if ($result) {
            $statusColumnF = $this->documentF->getLineColumnStatus();
            $quantityColumnF = $this->documentF->getLineQuantityColumn();
            $closeStatusF = $this->documentF->getLineDestroyedStatus();
            $lineF->$statusColumnF = $closeStatusF;
            $lineF->save();
            
            //get line of initial document
            $fk = $this->foreignKey;
            $lineI = $this->documentI->getModel($lineF->$fk, false);
            $lineColumnI = $this->documentI->getLineColumnStatus();

            $quantity = $this->documentF->getModelLinesByFK($lineI->id, $this->foreignKey)->where($statusColumnF, '<>', $closeStatusF)->sum($quantityColumnF);

            if ($quantity == 0) {
                $lineI->$lineColumnI = $this->documentI->getLineOpenStatus();
            } else {
                $lineI->$lineColumnI = $this->documentI->getLinePartiallyCloseStatus();
            }
            $lineI->save();

            //edit header status model
            $foreignKeyLine = $this->documentI->getForeignKeyLine();
            $this->updateDocumentStatus($this->documentI->getModel($lineI->$foreignKeyLine));

            $foreignKeyLineF = $this->documentF->getForeignKeyLine();
            $this->updateDocumentStatus($this->documentF->getModel($lineF->$foreignKeyLineF));

            return true;
        } else {
            return false;
        }
    }

    public function calculateLineStatus($id)
    {
        $statusColumnF = $this->documentF->getLineColumnStatus();
        $quantityColumnF = $this->documentF->getLineQuantityColumn();
        $closeStatusF = $this->documentF->getLineCloseStatus();

        $model = $this->documentI->getModel($id, false);

        $statusColumnI = $this->documentI->getLineColumnStatus();
        $closeStatusI = $this->documentI->getLineCloseStatus();
        $openStatusI = $this->documentI->getLineOpenStatus();
        $partiallyStatusI = $this->documentI->getLinePartiallyCloseStatus();

        $totalI = $model->quantity;
        $totalF = $this->documentF->getModelLinesByFK($id, $this->foreignKey)->where($statusColumnF, '<>', $closeStatusF)->sum($quantityColumnF);

        if ($totalF == 0) {
            $model->$statusColumnI = $openStatusI;
        } elseif ($totalI < $totalF) {
            throw new Exception("For some reason, final document total is bigger than initial document total...");
        } elseif ($totalI > $totalF) {
            $model->$statusColumnI = $partiallyStatusI;
        } elseif ($totalI == $totalF) {
            $model->$statusColumnI = $closeStatusI;
        }

        $model->save();
    }

    private function updateDocumentStatus($doc)
    {
        //header
        $statusColumnHeaderI = $this->documentI->getHeaderColumnStatus();
        $openStatusHeaderI = $this->documentI->getHeaderOpenStatus();
        $closeStatusHeaderI = $this->documentI->getHeaderCloseStatus();

        //lines
        $lines = $doc->lines;
        $statusColumnI = $this->documentI->getLineColumnStatus();
        $openStatusI = $this->documentI->getLineOpenStatus();
        $partiallyCloseStatusI = $this->documentI->getLinePartiallyCloseStatus();
        
        foreach ($lines as $line) {
            if ($line->$statusColumnI == $openStatusI || $line->$statusColumnI == $partiallyCloseStatusI) {
                $doc->$statusColumnHeaderI = $openStatusHeaderI;
                $doc->save();
                return;
            }
        }

        $doc->$statusColumnHeaderI = $closeStatusHeaderI;
        $doc->save();
    }
}
