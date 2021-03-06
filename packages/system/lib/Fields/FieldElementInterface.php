<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataInterface;
use DOMElement;

interface FieldElementInterface extends MetadataInterface
{
    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, SchemaInterface $section, EntryInterface $entry, FieldInterface $field = null);
}
