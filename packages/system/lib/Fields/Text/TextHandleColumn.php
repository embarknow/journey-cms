<?php

namespace Embark\CMS\Fields\Text;

use DOMElement;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldColumnTrait;
use Embark\CMS\Schemas\SchemaInterface;
use Widget;

class TextHandleColumn implements FieldColumnInterface
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
            $link = Widget::Anchor(
                (string)$data->handle,
                $url . '/edit/' . $entry->entry_id
            );
            $body->appendChild($link);
        }

        else {
            $body->setValue((string)$data->handle);
        }
    }
}
