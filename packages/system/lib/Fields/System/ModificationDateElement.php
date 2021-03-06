<?php

namespace Embark\CMS\Fields\System;

use DOMElement;
use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldElementInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\SystemDateTime;
use General;

class ModificationDateElement implements FieldElementInterface
{
    use MetadataTrait;

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, SchemaInterface $schema, EntryInterface $entry, FieldInterface $field = null)
    {
        $document = $wrapper->ownerDocument;
        $date = new SystemDateTime($entry->modification_date);
        $date = $date->toUserDateTime();

        $wrapper->appendChild(General::createXMLDateObject(
            $document, $date, 'modification-date'
        ));
    }
}
