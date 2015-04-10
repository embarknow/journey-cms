<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldColumnTrait;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;
use Widget;

class IdColumn implements FieldColumnInterface
{
    use FieldColumnTrait;

    public function appendBodyElement(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, $url)
    {
        $document = $wrapper->ownerDocument;
        $body = $document->createElement('td');
        $wrapper->appendChild($body);

        if ($this['editLink']) {
            $link = Widget::Anchor(
                (string)$entry->entry_id,
                $url . '/edit/' . $entry->entry_id
            );
            $body->appendChild($link);
        }

        else {
            $body->setValue((string)$entry->entry_id);
        }
    }
}