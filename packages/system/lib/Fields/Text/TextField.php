<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\Controller;
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
use TextFormatter;
use TextFormatterIterator;
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
        $document->load(Controller::locate('text'));
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

    public function appendDataFormatterSettings(SymphonyDOMElement $wrapper, MessageStack $errors, MetadataInterface $settings)
    {
        require_once LIB . '/class.textformatter.php';

        $document = $wrapper->ownerDocument;
        $iterator = new TextFormatterIterator();
        $label = Widget::Label(__('Text Formatter'));
        $options = [
            [null, false, __('None')]
        ];

        if ($iterator->valid()) {
            foreach ($iterator as $pathname) {
                $handle = TextFormatter::getHandleFromFilename(basename($pathname));
                $tf = TextFormatter::load($pathname);

                $options[] = [
                    $handle,
                    ($settings['formatter'] == $handle),
                    constant(sprintf('%s::NAME', get_class($tf)))
                ];
            }

            $label->appendChild(Widget::Select('formatter', $options));
        }

        else {
            $select = Widget::Select('formatter', $options);
            $select->setAttribute('disabled', 'disabled');
            $label->appendChild($select);
        }

        $wrapper->appendChild($label);
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
            $result->formatted = General::sanitize($new->value);
        }

        else if (isset($new)) {
            $result->handle = Lang::createHandle($new);
            $result->value = $new;
            $result->formatted = General::sanitize($new);
        }

        return $result;
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

    public function repairEntities($value)
    {
        return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
    }

    public function repairMarkup($value)
    {
        $tidy = new Tidy();
        $tidy->parseString(
            $value, array(
                'drop-font-tags'                => true,
                'drop-proprietary-attributes'   => true,
                'enclose-text'                  => true,
                'enclose-block-text'            => true,
                'hide-comments'                 => true,
                'numeric-entities'              => true,
                'output-xhtml'                  => true,
                'wrap'                          => 0,

                // HTML5 Elements:
                'new-blocklevel-tags'           => 'section nav article aside hgroup header footer figure figcaption ruby video audio canvas details datagrid summary menu',
                'new-inline-tags'               => 'time mark rt rp output progress meter',
                'new-empty-tags'                => 'wbr source keygen command'
            ), 'utf8'
        );

        return $tidy->body()->value;
    }
}
