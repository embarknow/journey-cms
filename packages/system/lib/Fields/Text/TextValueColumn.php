<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\Boolean;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Widget;

class TextValueColumn implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'editLink' => [
                'filter' =>     new Boolean()
            ]
        ]);
    }

    public function appendHeader(DOMElement $wrapper, SchemaInterface $schema, FieldInterface $field, $url)
    {
        $document = $wrapper->ownerDocument;
        $header = $document->createElement('th');
        $header->addClass('col');
        $wrapper->appendChild($header);

        // Add sorting information:
        if ($field['sorting'] instanceof MetadataInterface) {
            $direction = (
                'asc' === $field['sorting']['direction']
                    ? 'desc'
                    : 'asc'
            );

            $link = Widget::Anchor(
                $this['name'],
                $url . '?sort=' . $this['name'] . '&direction=' . $direction
            );
            $header->appendChild($link);
        }

        else {
            $header->setValue($this['name']);
        }
    }

    public function appendBody(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, FieldInterface $field, $url)
    {
        $data = $field['data']->read($schema, $entry, $field);
        $document = $wrapper->ownerDocument;
        $body = $document->createElement('td');
        $wrapper->appendChild($body);

        if ($this['editLink']) {
            $link = Widget::Anchor(
                (string)$data->value,
                $url . '/edit/' . $entry->entry_id
            );
            $body->appendChild($link);
        }

        else {
            $body->setValue((string)$data->value);
        }
    }
}