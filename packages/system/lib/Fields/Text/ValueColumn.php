<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldColumnTrait;
use Embark\CMS\Link;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;
use Widget;

class ValueColumn implements FieldColumnInterface
{
    use FieldColumnTrait;

    public function appendBodyTo(DOMElement $wrapper, EntryInterface $entry, Link $link)
    {
        $field = $this['field']->resolve();
        $data = $field->readData($entry, $this);
        $document = $wrapper->ownerDocument;
        $body = $document->createElement('dd');
        $wrapper->appendChild($body);

        if ('' === (string)$data->value) {
            $body->appendChild($document->createEntityReference('nbsp'));
        }

        else if ($this['editLink']) {
            $link = Widget::Anchor(
                (string)$data->value,
                $link . '/edit/' . $entry->entry_id
            );
            $body->appendChild($link);
        }

        else {
            $body->setValue((string)$data->value);
        }
    }
}