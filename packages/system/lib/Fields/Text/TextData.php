<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldDataInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\Controller;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\Structures\Guid;
use Context;
use Entry;
use Extension;
use MessageStack;
use Symphony;
use SymphonyDOMElement;
use TextFormatter;
use TextFormatterIterator;
use Widget;

/**
 * Used to access field data.
 */
class TextData implements FieldDataInterface
{
    use MetadataTrait;

    public function __construct()
    {
    }

    public function prepare(EntryInterface $entry, FieldInterface $field, $new = null, $old = null)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        // Start with the old value:
        if (isset($old->value)) {
            $result = $old;
        }

        else {
            $result = (object)[
                'handle' =>     null,
                'value' =>      null
            ];
        }

        // Import the new value on top:
        if (isset($new->value, $new->handle)) {
            $result->handle = $new->handle;
            $result->value = $new->value;
        }

        else if (isset($new->value)) {
            $result->handle = null;
            $result->value = $new->value;
        }

        else if (isset($new)) {
            $result->handle = null;
            $result->value = $new;
        }

        // Generate the handle:
        if (isset($result->value) && isset($result->handle) === false) {
            $result->handle = strtolower($new);
        }

        return $result;
    }

    public function validate(EntryInterface $entry, FieldInterface $field, $data)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        if (
            $field->settings()->required === 'yes'
            && (
                isset($data->value) === false
                || trim($data->value) == false
            )
        ) {
            throw new \Exception("'{$field->settings()->handle}' is a required field.");
        }

        yield true;
    }

    public function read(SchemaInterface $section, EntryInterface $entry, FieldInterface $field)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $rows = Symphony::Database()->query(
            "SELECT * FROM `data_%s_%s` WHERE `entry_id` = %s ORDER BY `id` ASC",
            array(
                $entry->section,
                $this->{'element-name'},
                $entry->id
            )
        );

        $statement = Symphony::Database()->prepare(sprintf(
            'select * from `data_%s_%s` where `entry_id` = ?',
            $section['resource']['handle'],
            $field['handle']
        ));
        $statement->bindValue(1, $entry->getId(), PDO::PARAM_INT);

        if (false === $statement->execute()) {
            return false;
        }

        var_dump($statement); exit;
    }

    public function write(SchemaInterface $section, EntryInterface $entry, FieldInterface $field, $data)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $field['schema']['handle'],
            $field['schema']['guid']
        );
        $statement = Symphony::Database()->prepare("
            insert into `{$table}` set
                entryId = :entryId,
                handle = :handle,
                value = :value,
                formatted = :formatted
            on duplicate key update
                value = :updateValue,
                formatted = :updateFormatted
        ");

        return $statement->execute([
            ':entryId' =>           $entry->getId(),
            ':handle' =>            $data->handle,
            ':value' =>             $data->value,
            ':updateValue' =>       $data->value,
            ':formatted' =>         $data->formatted,
            ':updateFormatted' =>   $data->formatted
        ]);
    }
}