<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldElementInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DOMElement;
use General;

class ModificationDateElement implements FieldElementInterface
{
    use MetadataTrait;

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, SchemaInterface $schema, EntryInterface $entry)
    {
        $document = $wrapper->ownerDocument;
        $date = new SystemDateTime($entry->modification_date);
        $date = $date->toUserDateTime();

        $wrapper->appendChild(General::createXMLDateObject(
            $document, $date, 'modification-date'
        ));
    }
}
