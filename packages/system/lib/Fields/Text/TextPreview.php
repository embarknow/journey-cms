<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldPreviewCaptionInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;
use Widget;

class TextPreview implements FieldPreviewCaptionInterface
{
    use MetadataTrait;

    public function appendTitleTo(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, Link $link)
    {
        $document = $wrapper->ownerDocument;
        $field = $this['field']->resolve();
        $data = $field->readData($schema, $entry, $this);

        $anchor = $document->createElement('a');
        $anchor->setAttribute('href', $link . '/edit/' . $entry->entry_id);
        $anchor->setValue((string)$data->value);
        $wrapper->appendChild($anchor);
    }

    public function appendCaptionTo(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, Link $link)
    {
        $document = $wrapper->ownerDocument;
        $field = $this['field']->resolve();
        $data = $field->readData($schema, $entry, $this);

        $caption = $document->createElement('caption');
        $wrapper->appendChild($caption);

        $text = $document->createDocumentFragment();
        $text->appendXml($data->formatted);
        $caption->appendChild($text);
    }
}