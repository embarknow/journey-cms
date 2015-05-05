<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Fields\FieldProcessorInterface;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

class Processor implements FieldProcessorInterface
{
    use MetadataTrait;

    public function finalize(EntryInterface $entry, FieldInterface $field, MetadataInterface $settings, $data)
    {
    }

    public function read(EntryInterface $entry, FieldInterface $field, MetadataInterface $settings)
    {
        // Read post data:
        if (isset($_POST['fields'])) {
            $handle = $field['handle'];
            $data = $field->prepareData($entry, $settings, (
                isset($_POST['fields'][$handle])
                    ? $_POST['fields'][$handle]
                    : null
            ));
        }

        // Read existing data:
        else {
            $data = $field->readData($entry, $settings);
        }

        return $data;
    }

    public function validate(EntryInterface $entry, FieldInterface $field, MetadataInterface $settings, $data)
    {
        return $field->validateData($entry, $settings, $data);
    }

    public function write(EntryInterface $entry, FieldInterface $field, MetadataInterface $settings, $data)
    {
        return $field->writeData($entry, $settings, $data);
    }
}