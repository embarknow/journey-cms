<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldPreviewCaptionInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;

class Preview implements FieldPreviewCaptionInterface
{
    use MetadataTrait;

    public function appendTitleTo(DOMElement $wrapper, EntryInterface $entry, Link $link)
    {
        $document = $wrapper->ownerDocument;
        $anchor = $document->createElement('a');
        $anchor->setAttribute('href', $link . '/edit/' . $entry->entry_id);
        $anchor->setValue($this->getTitle($entry));
        $wrapper->appendChild($anchor);
    }

    public function appendCaptionTo(DOMElement $wrapper, EntryInterface $entry, Link $link)
    {
        $document = $wrapper->ownerDocument;
        $field = $this['field']->resolve();
        $data = $field->readData($entry, $this);

        $caption = $document->createElement('caption');
        $wrapper->appendChild($caption);

        $text = $document->createDocumentFragment();
        $text->appendXml($this->getCaption($entry));
        $caption->appendChild($text);
    }

    public function getTitle(EntryInterface $entry)
    {
        $field = $this['field']->resolveInstanceOf(FieldInterface::class);
        $data = $field->readData($entry, $this);

        return $data->value;
    }

    public function getCaption(EntryInterface $entry)
    {
        $field = $this['field']->resolveInstanceOf(FieldInterface::class);
        $data = $field->readData($entry, $this);

        return $data->formatted;
    }
}