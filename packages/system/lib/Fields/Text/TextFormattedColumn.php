<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldColumnTrait;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;
use Widget;

class TextFormattedColumn implements FieldColumnInterface
{
    use FieldColumnTrait;

    public function appendBodyElement(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, $url)
    {
        $field = $this['field']->resolve();
        $data = $field->readData($schema, $entry, $this);
        $document = $wrapper->ownerDocument;
        $body = $document->createElement('dd');
        $body->addClass($this['size']);
        $wrapper->appendChild($body);

        if ($this['editLink']) {
            $link = $document->createElement('a');
            $link->setAttribute('href', $url . '/edit/' . $entry->entry_id);

            $text = $document->createDocumentFragment();
            $text->appendXml((string)$data->formatted);
            $link->appendChild($text);

            $body->appendChild($link);
        }

        else {
            $text = $document->createDocumentFragment();
            $text->appendXml((string)$data->formatted);
            $body->appendChild($text);
        }
    }
}