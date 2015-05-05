<?php

namespace Embark\CMS\Fields\Number;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\Controller as FieldController;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldRequiredException;
use Embark\CMS\Fields\FieldTrait;
use Embark\CMS\Formatters\Controller as FormatterController;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\UserDateTime;
use Embark\CMS\SystemDateTime;
use DateTime;
use DOMDocument;
use DOMElement;
use General;
use Lang;
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
        $document->load(FieldController::locate('date'));
        $this->fromXML($document->documentElement);
    }

    public function appendSchemaSettings(DOMElement $wrapper, MessageStack $errors)
    {
        $this->appendSchemaHandleSettings($wrapper, $errors);

        $wrapper->appendChild(Widget::Input('guid', $this->getGuid(), 'hidden'));
        $wrapper->appendChild(Widget::Input('type', 'date', 'hidden'));
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
                `value` double default null,
                unique key `entry_id` (`entry_id`),
                key `value` (`value`)
            )
        ");

        return $statement->execute();
    }

    public function prepareData(SchemaInterface $schema, EntryInterface $entry, MetadataInterface $settings, $data)
    {
        $result = (object)[
            'value' =>      null
        ];

        if ($data instanceof StdClass) {
            return $this->prepareData($schema, $entry, $settings, $data->value);
        }

        if (filter_var($data, FILTER_VALIDATE_INT)) {
            $result->value = (integer)filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        }

        else if (filter_var($data, FILTER_VALIDATE_FLOAT)) {
            $result->value = (float)filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT);
        }

        return $result;
    }

    public function validateData(SchemaInterface $schema, EntryInterface $entry, MetadataInterface $settings, $data)
    {
        // Field is required but no value was set:
        if ($settings['required'] && false === $settings['allow-zero'] && 0 == $data->value) {
            throw new FieldRequiredException('Value is required.');
        }

        else if ($settings['required'] && false === isset($data->value)) {
            throw new FieldRequiredException('Value is required.');
        }

        if ($data->value < $settings['min-value']) {
            throw new MinValueException('Value is below minimum allowed.');
        }

        if ($data->value > $settings['max-value']) {
            throw new MaxValueException('Value is above maximum allowed.');
        }

        return true;
    }

    public function writeData(SchemaInterface $schema, EntryInterface $entry, MetadataInterface $settings, $data)
    {
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
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
