<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldColumnTrait;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\SystemDateTime;
use DOMElement;
use Widget;

class CreationDateColumn implements FieldColumnInterface
{
    use FieldColumnTrait;

    public function appendBodyElement(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, $url)
    {
        $document = $wrapper->ownerDocument;
        $date = new SystemDateTime($entry->creation_date);
        $date = $date->toUserDateTime();
        $body = $document->createElement('td');
        $wrapper->appendChild($body);

        if ($this['editLink']) {
            $link = Widget::Anchor(
                $date->format(__SYM_DATETIME_FORMAT__),
                $url . '/edit/' . $entry->entry_id
            );
            $body->appendChild($link);
        }

        else {
            $body->setValue($date->format(__SYM_DATETIME_FORMAT__));
        }
    }
}