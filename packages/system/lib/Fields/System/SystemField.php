<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Fields\Controller;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\Integer;
use Context;
use DOMDocument;
use Entry;
use MessageStack;
use Symphony;
use SymphonyDOMElement;
use Widget;

class SystemField implements FieldInterface
{
    use MetadataTrait;

    public function __construct()
    {
        // TODO: Figure out a better way to do this because
        // $this->fromXML is called twice when Controller::read is used

        // Load defaults from disk:
        $document = new DOMDocument();
        $document->load(Controller::locate('system'));
        $this->fromXML($document->documentElement);
    }
}