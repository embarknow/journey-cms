<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Link;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;

interface FieldPreviewCaptionInterface extends FieldPreviewInterface
{
    public function appendCaptionTo(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, Link $link);
}