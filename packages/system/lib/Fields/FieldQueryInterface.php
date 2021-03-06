<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Schemas\SchemaSelectQuery;
use Embark\CMS\Metadata\MetadataInterface;

interface FieldQueryInterface extends MetadataInterface
{
    /**
     * Append a filtering or sorting query to a schema query.
     *
     * @param   SchemaSelectQuery   $page
     * @param   SchemaInterface     $entry
     * @param   FieldInterface|null $field
     *  Instance of the field being filtered or sorted on, or null
     *  when the field is specified in metadata.
     */
    public function appendQuery(SchemaSelectQuery $query, SchemaInterface $schema, FieldInterface $field = null);
}
