<?php

namespace Embark\CMS\Fields\Boolean;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldPreviewCaptionInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;
use Widget;

class Preview implements FieldPreviewCaptionInterface
{
    use MetadataTrait;

    public function appendTitleTo(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, Link $link)
    {
        $document = $wrapper->ownerDocument;
        $anchor = $document->createElement('a');
        $anchor->setAttribute('href', $link . '/edit/' . $entry->entry_id);
        $anchor->setValue($this->getTitle($schema, $entry));
        $wrapper->appendChild($anchor);
    }

    public function appendCaptionTo(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, Link $link)
    {
        $document = $wrapper->ownerDocument;
        $caption = $document->createElement('caption');
        $caption->setValue($this->getCaption($schema, $entry));
        $wrapper->appendChild($caption);
    }

    public function getTitle(SchemaInterface $schema, EntryInterface $entry)
    {
        $field = $this['field']->resolveInstanceOf(FieldInterface::class);
        $data = $field->readData($schema, $entry, $this);

        return (
            $data->value
                ? 'Yes'
                : 'No'
        );
    }

    public function getCaption(SchemaInterface $schema, EntryInterface $entry)
    {
        return $this->getTitle($schema, $entry);
    }
}