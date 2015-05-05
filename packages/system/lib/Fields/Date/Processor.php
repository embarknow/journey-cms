<?php

namespace Embark\CMS\Fields\Date;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldProcessorTrait;
use Embark\CMS\Fields\FieldProcessorInterface;
use Embark\CMS\Fields\FieldRequiredException;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\UserDateTime;
use Embark\CMS\SystemDateTime;

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
            $post = (
                isset($_POST['fields'][$handle])
                    ? $_POST['fields'][$handle]
                    : null
            );
            $date = null;

            if (strlen(trim($post)) !== 0) {
                $date = (new UserDateTime($post))->toSystemDateTime();
            }

            else if ($settings['autofill']) {
                $date = new SystemDateTime();
            }

            $data = $field->prepareData($entry, $settings, $date);
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