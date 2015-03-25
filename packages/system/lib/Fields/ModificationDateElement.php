<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DOMElement;
use Entry;
use Field;
use General;
use Section;

class ModificationDateElement implements MetadataInterface
{
    use MetadataTrait;

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, Section $section, Entry $entry)
    {
        $document = $wrapper->ownerDocument;
        $date = new SystemDateTime($entry->modification_date);
        $date = $date->toUserDateTime();

        $wrapper->appendChild(General::createXMLDateObject(
            $document, $date, 'modification-date'
        ));
    }
}
