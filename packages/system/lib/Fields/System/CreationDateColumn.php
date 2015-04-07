<?php

namespace Embark\CMS\Fields\System;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\Boolean;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use DOMElement;
use Widget;

class CreationDateColumn implements MetadataInterface
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

    public function appendHeader(DOMElement $wrapper)
    {
        $wrapper->appendChild(Widget::TableColumn([
            $this['name'], 'col'
        ]));
    }

    public function appendBody(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, FieldInterface $field, $url)
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