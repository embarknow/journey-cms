<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldElementInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DOMElement;
use General;

class CreationDateElement implements FieldElementInterface
{
    use MetadataTrait;

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, SchemaInterface $schema, EntryInterface $entry, FieldInterface $field = null)
    {
        $document = $wrapper->ownerDocument;
        $date = new SystemDateTime($entry->creation_date);
        $date = $date->toUserDateTime();

        $wrapper->appendChild(General::createXMLDateObject(
            $document, $date, 'creation-date'
        ));
    }
}
