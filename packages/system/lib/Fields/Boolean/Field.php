<?php

namespace Embark\CMS\Fields\Boolean;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\Controller as FieldController;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldRequiredException;
use Embark\CMS\Fields\FieldTrait;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Schemas\SchemaInterface;
use DOMDocument;
use DOMElement;
use MessageStack;
use Symphony;
use StdClass;
use Widget;

/**
 * A collection of information about the field type.
 */
class Field implements FieldInterface
{
    use FieldTrait;

    public function __construct()
    {
        // TODO: Figure out a better way to do this because
        // $this->fromXML is called twice when Controller::read is used

        // Load defaults from disk:
        $document = new DOMDocument();
        $document->load(FieldController::locate('boolean'));
        $this->fromXML($document->documentElement);
    }

    public function appendSchemaSettings(DOMElement $wrapper, MessageStack $errors)
    {
        $this->appendSchemaHandleSettings($wrapper, $errors);

        $wrapper->appendChild(Widget::Input('guid', $this->getGuid(), 'hidden'));
        $wrapper->appendChild(Widget::Input('type', 'boolean', 'hidden'));
    }

    public function createSchema(SchemaInterface $schema)
    {
        // TODO: Throw an exception if $field['data'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $this['handle'],
            $this->getGuid()
        );
        $statement = Symphony::Database()->prepare("
            create table if not exists `{$table}` (
                `entry_id` int(11) unsigned not null,
                `value` boolean default false,
                unique key `entry_id` (`entry_id`),
                key `value` (`value`)
            )
        ");

        return $statement->execute();
    }

    public function prepareData(EntryInterface $entry, MetadataInterface $settings, $data)
    {
        $result = (object)[
            'value' =>      false
        ];

        if ($data instanceof StdClass) {
            return $this->prepareData($entry, $settings, $data->value);
        }

        $result->value = filter_var($data, FILTER_VALIDATE_BOOLEAN);

        return $result;
    }

    public function validateData(EntryInterface $entry, MetadataInterface $settings, $data)
    {
        // Field is required but no value was set:
        if (
            $settings['required']
            && $data->value === false
        ) {
            throw new FieldRequiredException('Value is required.');
        }

        return true;
    }

    public function writeData(EntryInterface $entry, MetadataInterface $settings, $data)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $entry['resource']['handle'],
            $this['handle'],
            $this->getGuid()
        );
        $statement = Symphony::Database()->prepare("
            insert into `{$table}` set
                entry_id = :entryId,
                value = :value
            on duplicate key update
                value = :updateValue
        ");

        return $statement->execute([
            ':entryId' =>           $entry->entry_id,
            ':value' =>             $data->value,
            ':updateValue' =>       $data->value
        ]);
    }
}
