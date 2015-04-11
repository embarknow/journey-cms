<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\Filters\Guid;
use Symphony;

interface FieldSchemaInterface extends MetadataInterface
{
    public function create(SchemaInterface $schema, FieldInterface $field);

    public function rename(SchemaInterface $newSchema, FieldInterface $newField, SchemaInterface $oldSchema, FieldInterface $oldField);

    public function delete(SchemaInterface $schema, FieldInterface $field);
}
