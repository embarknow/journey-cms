<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataInterface;
use Entry;

interface FieldDataInterface extends MetadataInterface
{
    public function prepare(EntryInterface $entry, FieldInterface $field, $new = null, $old = null);

    public function validate(EntryInterface $entry, FieldInterface $field, $data);

    public function read(SchemaInterface $section, EntryInterface $entry, FieldInterface $field);

    public function write(SchemaInterface $section, EntryInterface $entry, FieldInterface $field, $data);
}