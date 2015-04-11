<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Formatters\Controller as FormatterController;
use Embark\CMS\Fields\Controller as FieldController;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldRequiredException;
use Embark\CMS\Fields\FieldTrait;
use Embark\CMS\Metadata\Filters\Integer;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Schemas\SchemaInterface;
use DOMDocument;
use General;
use Lang;
use MessageStack;
use PDO;
use Symphony;
use SymphonyDOMElement;
use Widget;

/**
 * A collection of information about the field type.
 */
class TextField implements FieldInterface
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

    public function appendSchemaSettings(SymphonyDOMElement $wrapper, MessageStack $errors)
    {
        $this->appendSchemaHandleSettings($wrapper, $errors);
        // $this->appendFormatterSettings($wrapper, $errors);

        $wrapper->appendChild(Widget::Input('guid', $this->getGuid(), 'hidden'));
        $wrapper->appendChild(Widget::Input('type', 'text', 'hidden'));
    }

    public function appendSchemaHandleSettings(SymphonyDOMElement $wrapper, MessageStack $errors)
    {
        $document = $wrapper->ownerDocument;
        $label = Widget::Label(__('Handle'));
        $label->setAttribute('class', 'field-handle');
        $label->appendChild(Widget::Input('handle', $this['handle']));

        if ($errors->{'handle'}) {
            $label = Widget::wrapFormElementWithError($label, $errors->{'handle'});
        }

        $wrapper->appendChild($label);
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

    public function renameSchema(SchemaInterface $newSchema, FieldInterface $newField, SchemaInterface $oldSchema, FieldInterface $oldField)
    {
        // TODO: Throw an exception if $*Field['schema'] is unset.
        $oldTable = Symphony::Database()->createDataTableName(
            $oldSchema['resource']['handle'],
            $oldField['handle'],
            $oldField->getGuid()
        );
        $newTable = Symphony::Database()->createDataTableName(
            $newSchema['resource']['handle'],
            $newField['handle'],
            $newField->getGuid()
        );
        $statement = Symphony::Database()->prepare("
            alter table `{$oldTable}`
            rename to `{$newTable}
        ");

        return $statement->execute();
    }

    public function deleteSchema(SchemaInterface $schema)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $this['handle'],
            $this->getGuid()
        );
        $statement = Symphony::Database()->prepare("
            drop table if exists `{$table}`
        ");

        return $statement->execute();
    }

    public function prepareData(SchemaInterface $schema, EntryInterface $entry, MetadataInterface $settings, $new = null, $old = null)
    {
        // Start with the old value:
        if (isset($old->value, $new->handle)) {
            $result = $old;
        }

        else {
            $result = (object)[
                'handle' =>     null,
                'value' =>      null,
                'formatted' =>  null
            ];
        }

        // Import the new value on top:
        if (isset($new->value, $new->handle, $new->formatted)) {
            $result->handle = $new->handle;
            $result->value = $new->value;
            $result->formatted = $new->formatted;
        }

        else if (isset($new->value)) {
            $result->handle = Lang::createHandle($new->value);
            $result->value = $new->value;
            $result->formatted = $this->formatValue($settings, $new->value);
        }

        else if (isset($new)) {
            $result->handle = Lang::createHandle($new);
            $result->value = $new;
            $result->formatted = $this->formatValue($settings, $new);
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

    public function validateData(SchemaInterface $schema, EntryInterface $entry, MetadataInterface $settings, $data)
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
            throw new TextLengthException('Value is longer than allowed.');
        }

        return true;
    }

    public function deleteData(SchemaInterface $schema, EntryInterface $entry, MetadataInterface $settings)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $this['handle'],
            $this->getGuid()
        );

        $statement = Symphony::Database()->prepare("
            delete from `$table` where
                `entry_id` = :entryId
        ");
        $statement->bindValue(':entryId', $entry->entry_id, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function readData(SchemaInterface $schema, EntryInterface $entry, MetadataInterface $settings)
    {
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $this['handle'],
            $this->getGuid()
        );

        $statement = Symphony::Database()->prepare("
            select * from `$table` where
                `entry_id` = :entryId
        ");
        $statement->bindValue(':entryId', $entry->entry_id, PDO::PARAM_INT);
        $data = null;

        if ($statement->execute()) {
            $data = $this->prepareData($schema, $entry, $settings, $statement->fetch());
        }

        return $data;
    }

    public function writeData(SchemaInterface $schema, EntryInterface $entry, MetadataInterface $settings, $data)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
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
