<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Link;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Schemas\SchemaSelectQuery;
use Embark\CMS\Metadata\MetadataInterface;
use DOMElement;

interface FieldColumnInterface extends MetadataInterface
{
    public function appendSortingQuery(SchemaSelectQuery $query, SchemaInterface $schema, $direction = null);

    public function appendHeaderTo(DOMElement $wrapper, EntryInterface $entry, Link $link);

    public function appendBodyTo(DOMElement $wrapper, EntryInterface $entry, Link $link);
}
