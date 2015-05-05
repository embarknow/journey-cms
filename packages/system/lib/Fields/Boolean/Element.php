<?php

namespace Embark\CMS\Fields\Boolean;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldElementInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;

class Element implements FieldElementInterface
{
    use MetadataTrait;

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, SchemaInterface $schema, EntryInterface $entry, FieldInterface $field = null)
    {
        if (false === isset($field)) {
            $field = $this['field']->resolveInstanceOf(FieldInterface::class);
        }

        $document = $wrapper->ownerDocument;
        $data = $field->readData($schema, $entry, $field);

        $element = $document->createElement($field['handle']);
        $element->setValue(
        	$data->value
        		? 'yes'
        		: 'no'
        );
        $wrapper->appendChild($element);
    }
}
