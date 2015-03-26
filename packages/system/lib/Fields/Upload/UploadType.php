<?php

namespace Embark\CMS\Fields\Upload;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Fields\Controller;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\Integer;
use Context;
use DOMDocument;
use Entry;
use MessageStack;
use Symphony;
use SymphonyDOMElement;
use Widget;

/**
 * A collection of information about the field type.
 */
class UploadType implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'data' => [
                'required' =>   true,
                'type' =>       new UploadData()
            ]
        ]);

        // // TODO: Figure out a better way to do this because
        // // $this->fromXML is called twice when Controller::read is used

        // // Load defaults from disk:
        // $document = new DOMDocument();
        // $document->load(Controller::locate('text'));
        // $this->fromXML($document->documentElement);
    }
}