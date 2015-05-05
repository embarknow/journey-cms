<?php

namespace Embark\CMS\Fields\System;

use DOMElement;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldColumnTrait;
use Embark\CMS\Link;
use Widget;

class IdColumn implements FieldColumnInterface
{
    use FieldColumnTrait;

    public function appendBodyTo(DOMElement $wrapper, EntryInterface $entry, Link $link)
    {
        $document = $wrapper->ownerDocument;
        $body = $document->createElement('dd');
        $wrapper->appendChild($body);

        if ($this['editLink']) {
            $link = Widget::Anchor(
                (string)$entry->entry_id,
                $link . '/edit/' . $entry->entry_id
            );
            $body->appendChild($link);
        }

        else {
            $body->setValue((string)$entry->entry_id);
        }
    }
}
