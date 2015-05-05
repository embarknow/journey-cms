<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Metadata\MetadataInterface;

interface FieldProcessorInterface extends MetadataInterface
{
    public function finalize(EntryInterface $entry, FieldInterface $field, MetadataInterface $settings, $data);

    public function read(EntryInterface $entry, FieldInterface $field, MetadataInterface $settings);

    public function validate(EntryInterface $entry, FieldInterface $field, MetadataInterface $settings, $data);

    public function write(EntryInterface $entry, FieldInterface $field, MetadataInterface $settings, $data);
}