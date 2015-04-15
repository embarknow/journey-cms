<?php

namespace Embark\CMS\Fields\System;

use DOMElement;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldColumnTrait;
use Embark\CMS\Link;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\SystemDateTime;
use Widget;

class ModificationDateColumn implements FieldColumnInterface
{
    use FieldColumnTrait;

    public function appendBodyTo(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, Link $link)
    {
        $document = $wrapper->ownerDocument;
        $date = new SystemDateTime($entry->modification_date);
        $date = $date->toUserDateTime();
        $body = $document->createElement('dd');
        $wrapper->appendChild($body);

        if ($this['editLink']) {
            $link = Widget::Anchor(
                $date->format(__SYM_DATETIME_FORMAT__),
                $link . '/edit/' . $entry->entry_id
            );
            $body->appendChild($link);
        }

        else {
            $body->setValue($date->format(__SYM_DATETIME_FORMAT__));
        }
    }
}
