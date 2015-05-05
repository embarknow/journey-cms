<?php

namespace Embark\CMS\Fields\Boolean;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldColumnTrait;
use Embark\CMS\Link;
use DOMElement;
use Widget;

class Column implements FieldColumnInterface
{
    use FieldColumnTrait;

    public function appendBodyTo(DOMElement $wrapper, EntryInterface $entry, Link $link)
    {
        $field = $this['field']->resolve();
        $data = $field->readData($entry, $this);
        $document = $wrapper->ownerDocument;
        $body = $document->createElement('dd');
        $wrapper->appendChild($body);

        if ($this['editLink']) {
            $link = Widget::Anchor(
                (string)$data->value,
                $link . '/edit/' . $entry->entry_id
            );
            $body->appendChild($link);
            $body = $link;
        }

        if (true === $data->value) {
            $body->setValue('Yes');
        }

        else {
            $body->setValue('No');
        }
    }
}