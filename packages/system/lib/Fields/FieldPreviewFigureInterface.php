<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Link;
use DOMElement;

interface FieldPreviewFigureInterface extends FieldPreviewInterface
{
    public function appendFigureTo(DOMElement $wrapper, EntryInterface $entry, Link $link);
}