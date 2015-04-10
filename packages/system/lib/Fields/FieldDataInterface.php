<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Entry;

interface FieldDataInterface extends MetadataInterface
{
    public function prepare(SchemaInterface $schema, EntryInterface $entry, FieldInterface $field, $new = null, $old = null);

    public function validate(SchemaInterface $schema, EntryInterface $entry, FieldInterface $field, $data);

    public function read(SchemaInterface $schema, EntryInterface $entry, FieldInterface $field);

    public function write(SchemaInterface $schema, EntryInterface $entry, FieldInterface $field, $data);
}
