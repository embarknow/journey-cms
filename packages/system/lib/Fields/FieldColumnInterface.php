<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Actors\Schemas\DatasourceQuery;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataInterface;
use DOMElement;

interface FieldColumnInterface extends MetadataInterface
{
    public function appendSortingQuery(DatasourceQuery $query, SchemaInterface $schema, $direction = null);

    public function appendHeaderElement(DOMElement $wrapper, $url);

    public function appendBodyElement(DOMElement $wrapper, SchemaInterface $schema, EntryInterface $entry, $url);
}
