<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Schemas\SchemaInterface;
use DOMElement;

interface FieldPreviewInterface extends MetadataInterface
{
    public function appendTitleTo(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, Link $link);
}