<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Fields\Controller;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldTrait;
use DOMDocument;

class SystemField implements FieldInterface
{
    use FieldTrait;

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