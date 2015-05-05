<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\Controller as FieldController;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldRequiredException;
use Embark\CMS\Fields\FieldTrait;
use Embark\CMS\Formatters\Controller as FormatterController;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Schemas\SchemaInterface;
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
        $document->load(FieldController::locate('text'));
        $this->fromXML($document->documentElement);
    }

    public function appendSchemaSettings(DOMElement $wrapper, MessageStack $errors)
    {
        $this->appendSchemaHandleSettings($wrapper, $errors);

        $wrapper->appendChild(Widget::Input('guid', $this->getGuid(), 'hidden'));
        $wrapper->appendChild(Widget::Input('type', 'text', 'hidden'));
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
                `handle` varchar(255) default null,
                `value` text default null,
                `formatted` text default null,
                unique key `entry_id` (`entry_id`),
                key `handle` (`entry_id`, `handle`),
                fulltext key `value` (`value`),
                fulltext key `formatted` (`formatted`)
            )
        ");

        return $statement->execute();
    }

    public function prepareData(EntryInterface $entry, MetadataInterface $settings, $data)
    {
        $result = (object)[
            'handle' =>     null,
            'value' =>      null,
            'formatted' =>  null
        ];

        if (isset($data->value, $data->handle, $data->formatted)) {
            return $data;
        }

        if ($data instanceof StdClass) {
            return $this->prepareData($entry, $settings, $data->value);
        }

        if (is_string($data) && strlen(trim($data)) !== 0) {
            $result->handle = Lang::createHandle($data);
            $result->value = $data;
            $result->formatted = $this->formatValue($settings, $data);
        }

        return $result;
    }

    protected function formatValue(MetadataInterface $settings, $value)
    {
        if (isset($settings['formatter'])) {
            $formatter = FormatterController::read($settings['formatter']);

            return $formatter->format($value);
        }

        return General::sanitize($value);
    }

    public function validateData(EntryInterface $entry, MetadataInterface $settings, $data)
    {
        // Field is required but no value was set:
        if (
            $settings['required']
            && (
                isset($data->value) === false
                || trim($data->value) == false
            )
        ) {
            throw new FieldRequiredException('Value is required.');
        }

        // The value is longer than max-length:
        if (
            isset($settings['max-length'], $data->value)
            && $settings['max-length'] > 0
            && strlen($data->value) > $settings['max-length']
        ) {
            throw new MaxLengthException('Value is longer than allowed.');
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
                handle = :handle,
                value = :value,
                formatted = :formatted
            on duplicate key update
                handle = :updatedHandle,
                value = :updateValue,
                formatted = :updateFormatted
        ");

        return $statement->execute([
            ':entryId' =>           $entry->entry_id,
            ':handle' =>            $data->handle,
            ':updatedHandle' =>     $data->handle,
            ':value' =>             $data->value,
            ':updateValue' =>       $data->value,
            ':formatted' =>         $data->formatted,
            ':updateFormatted' =>   $data->formatted
        ]);
    }
}
