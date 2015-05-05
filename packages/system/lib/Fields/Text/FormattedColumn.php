<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldColumnTrait;
use Embark\CMS\Link;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;
use Widget;

class FormattedColumn implements FieldColumnInterface
{
    use FieldColumnTrait;

    public function appendBodyTo(DOMElement $wrapper, EntryInterface $entry, Link $link)
    {
        $field = $this['field']->resolve();
        $data = $field->readData($entry, $this);
        $document = $wrapper->ownerDocument;
        $body = $document->createElement('dd');
        $wrapper->appendChild($body);

        if ('' === (string)$data->formatted) {
            $body->appendChild($document->createEntityReference('nbsp'));
        }

        else if ($this['editLink']) {
            $link = $document->createElement('a');
            $link->setAttribute('href', $link . '/edit/' . $entry->entry_id);

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