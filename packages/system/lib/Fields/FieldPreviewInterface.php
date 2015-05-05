<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataInterface;
use DOMElement;

interface FieldPreviewInterface extends MetadataInterface
{
    public function appendTitleTo(DOMElement $wrapper, EntryInterface $entry, Link $link);

    public function getTitle(EntryInterface $entry);
}