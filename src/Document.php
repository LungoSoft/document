<?php

namespace Lungo\Doc;

use Lungo\Doc\Interfaces\Document as DocumentI;

class Document implements DocumentI
{
    protected $headerClass, $lineClass, $foreignKeyLine;

    public function getForeignKeyLine () 
    {
        return $this->foreignKeyLine;
    }

    protected $headerColumnStatus = 'status';

    public function getHeaderColumnStatus () 
    {
        return $this->headerColumnStatus;
    }

    protected $headerCloseStatus = 'Cerrado';

    public function getHeaderCloseStatus () 
    {
        return $this->headerCloseStatus;
    } 

    protected $headerDestroyedStatus = 'Cancelado';

    public function getHeaderDestroyedStatus () 
    {
        return $this->headerDestroyedStatus;
    }

    protected $headerOpenStatus = 'Abierto';

    public function getHeaderOpenStatus () 
    {
        return $this->headerOpenStatus;
    }

    protected $lineColumnStatus = 'status';

    public function getLineColumnStatus () 
    {
        return $this->lineColumnStatus;
    }

    protected $lineCloseStatus = 'Cerrado';

    public function getLineCloseStatus () 
    {
        return $this->lineCloseStatus;
    }

    protected $linePartiallyCloseStatus = 'Parcialmente Cerrado';

    public function getLinePartiallyCloseStatus () 
    {
        return $this->linePartiallyCloseStatus;
    }

    protected $lineDestroyedStatus = 'Cancelado';

    public function getLineDestroyedStatus () 
    {
        return $this->lineDestroyedStatus;
    }

    protected $lineOpenStatus = 'Abierto';

    public function getLineOpenStatus () 
    {
        return $this->lineOpenStatus;
    }

    protected $lineQuantityColumn = 'quantity';

    public function getLineQuantityColumn () 
    {
        return $this->lineQuantityColumn;
    }

    public function create(array &$header, array &$lines)
    {
        $headerClass = $this->headerClass;
        $lineClass = $this->lineClass;

        //create header
        $header[$this->headerColumnStatus] = $this->headerOpenStatus;
        $model = $headerClass::create($header);
        $header['id'] = $model->id;

        //create lines
        foreach ($lines as $key => $line) {
            $line[$this->foreignKeyLine] = $model->id;
            $line[$this->lineColumnStatus] = $this->lineOpenStatus;

            $modelLine = $lineClass::create($line);

            $lines[$key]['id'] = $modelLine->id;
        }

        return $this;
    }

    public function editHeader($id, array $header)
    {
        return $this->getModels($id, 0, function ($model) use ($header) {
            $model->update($header);
        });
    }

    public function editLine($id, $lineId, array $line)
    {
        return $this->getModels($id, $lineId, function ($model, $lineM) use ($line) {
            $lineM->update($line);
        });
    }

    public function destroyHeader($id)
    {
        $column = $this->headerColumnStatus;
        $destroyedStatus = $this->headerDestroyedStatus;
        $column2 = $this->lineColumnStatus;
        $destroyedStatus2 = $this->lineDestroyedStatus;

        return $this->getModels($id, 0, function ($model) use ($column, $destroyedStatus, $column2, $destroyedStatus2) {
            $model->$column = $destroyedStatus;
            $model->save();

            $lines = $model->lines;
            foreach ($lines as $ln) {
                $ln->$column2 = $destroyedStatus2;
                $ln->save();
            }
        });
    }

    public function destroyLine($id, $lineId)
    {
        $column = $this->headerColumnStatus;
        $destroyedStatus = $this->headerDestroyedStatus;
        $column2 = $this->lineColumnStatus;
        $destroyedStatus2 = $this->lineDestroyedStatus;

        return $this->getModels($id, $lineId, function ($model, $line) use ($column, $destroyedStatus, $column2, $destroyedStatus2) {
            $line->$column = $this->lineDestroyedStatus;
            $line->save();
        });
    }

    public function canEdit($model, $lineId = 0)
    {
        //validate header
        $headerColumn = $this->headerColumnStatus;
        $lineColumn = $this->lineColumnStatus;

        if ($model->$headerColumn == $this->headerOpenStatus) { //validate if header is open

            $lines = $model->lines;
            foreach ($lines as $line) {
                if ($lineId) { //validate a line, then, ....
                    if ($lineId == $line->id) { //just validate that line
                        return $line->$lineColumn == $this->lineOpenStatus;
                    }
                } else { //just validate header, then, validate every single line
                    if ($line->$lineColumn != $this->lineOpenStatus) {
                        return false;
                    }
                }
            }

        } else {
            return false;
        }

        if ($lineId) {
            return false;
        } else {
            return true;
        }
    }

    public function getModels($id, $lineId, $callback)
    {
        $model = $this->getModel($id);

        //verify if model and line can destroy
        if ($this->canEdit($model, $lineId)) {
            if ($lineId) {
                $line = $this->getModel($lineId, false);
                $callback($model, $line);
            } else {
                $callback($model);
            }

            return true;
        }

        return false;
    }

    public function getModel($id, $header = true)
    {
        if ($header) {
            $headerClass = $this->headerClass;
            return $headerClass::find($id);
        } else {
            $lineClass = $this->lineClass;
            return $lineClass::find($id);
        }
    }

    public function getModelLinesByFK($value, $fk)
    {
        $lineClass = $this->lineClass;
        return $lineClass->where($fk, $value);
    }
}
