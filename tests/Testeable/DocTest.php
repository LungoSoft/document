<?php 

namespace Lungo\Doc\Test\Testeable;

use Lungo\Doc\Document;

class DocTest extends Document
{
    protected $headerClass = '\\Lungo\\Doc\\Test\\Testeable\\HeaderModel'; 

    protected $lineClass = '\\Lungo\\Doc\\Test\\Testeable\\LineModel';

    protected $foreignKeyLine = 'header_id';
}
