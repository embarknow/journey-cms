<?php

namespace Embark\CMS\Fields\Text;

use Extension;
use PDO;
use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\Controller;
use Embark\CMS\Fields\FieldDataInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldRequiredException;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use Embark\CMS\Metadata\Filters\Guid;
use Embark\CMS\Metadata\Filters\Integer;
use Context;
use Entry;
use General;
use Lang;
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
        $this->setSchema([
            'required' => [
                'filter' =>   new Boolean()
            ]
        ]);
    }

    public function prepare(SchemaInterface $schema, EntryInterface $entry, FieldInterface $field, $new = null, $old = null)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
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
            $result->formatted = General::sanitize($new->value);
        }

        else if (isset($new)) {
            $result->handle = Lang::createHandle($new);
            $result->value = $new;
            $result->formatted = General::sanitize($new);
        }

        return $result;
    }

    public function validate(SchemaInterface $schema, EntryInterface $entry, FieldInterface $field, $data)
    {
        // Field is required but no value was set:
        if (
            $this['required']
            && (
                isset($data->value) === false
                || trim($data->value) == false
            )
        ) {
            throw new FieldRequiredException('Value is required.');
        }

        // The value is longer than max-length:
        if (
            isset($this['max-length'], $data->value)
            && $this['max-length'] > 0
            && strlen($data->value) > $this['max-length']
        ) {
            throw new TextLengthException('Value is longer than allowed.');
        }

        return true;
    }

    public function delete(SchemaInterface $schema, EntryInterface $entry, FieldInterface $field)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $field['schema']['handle'],
            $field->getGuid()
        );

        // But what if there's an error when deleting entries?...

        // Because we're doing this inside of a transaction it is not possible to corrupt data by accident, and if you cannot delete any one of the entries selected, they will all still exist on page load.

        // Guess who forgot to save...

        $statement = Symphony::Database()->prepare("
            delete from `$table` where
                `entry_id` = :entryId
        ");
        $statement->bindValue(':entryId', $entry->entry_id, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function read(SchemaInterface $schema, EntryInterface $entry, FieldInterface $field)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $field['schema']['handle'],
            $field->getGuid()
        );

        $statement = Symphony::Database()->prepare("
            select * from `$table` where
                `entry_id` = :entryId
        ");
        $statement->bindValue(':entryId', $entry->entry_id, PDO::PARAM_INT);
        $data = null;

        if ($statement->execute()) {
            $data = $this->prepare($schema, $entry, $field, $statement->fetch());
        }

        return $data;
    }

    public function write(SchemaInterface $schema, EntryInterface $entry, FieldInterface $field, $data)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $field['schema']['handle'],
            $field->getGuid()
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
