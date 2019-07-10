<?php 

namespace Lungo\Doc\Interfaces;

interface Document
{
    public function create(array &$header, array &$lines);

    public function editHeader($id, array $header);

    public function editLine($id, $lineId, array $line);

    public function destroyHeader($id);

    public function destroyLine($id, $lineId);

    public function canEdit($model, $lineId = 0);
}
