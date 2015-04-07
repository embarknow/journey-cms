<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldSchemaInterface;
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
class TextSchema implements FieldSchemaInterface
{
    use MetadataTrait;

    public function create(SchemaInterface $schema, FieldInterface $field)
    {
        // TODO: Throw an exception if $field['data'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $field['schema']['handle'],
            $field['schema']['guid']
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

    public function rename(SchemaInterface $newSchema, FieldInterface $newField, SchemaInterface $oldSchema, FieldInterface $oldField)
    {
        // TODO: Throw an exception if $*Field['schema'] is unset.
        $oldTable = Symphony::Database()->createDataTableName(
            $oldSchema['resource']['handle'],
            $oldField['schema']['handle'],
            $oldField['schema']['guid']
        );
        $newTable = Symphony::Database()->createDataTableName(
            $newSchema['resource']['handle'],
            $newField['schema']['handle'],
            $newField['schema']['guid']
        );
        $statement = Symphony::Database()->prepare("
            alter table `{$oldTable}`
            rename to `{$newTable}
        ");

        return $statement->execute();
    }

    public function delete(SchemaInterface $schema, FieldInterface $field)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $field['schema']['handle'],
            $field['schema']['guid']
        );
        $statement = Symphony::Database()->prepare("
            drop table if exists `{$table}`
        ");

        return $statement->execute();
    }

    public function appendColumns(SymphonyDOMElement $wrapper)
    {
        $document = $wrapper->ownerDocument;

        $column = $document->createElement('th');
        $column->addClass('col');
        $column->setValue($this['handle']);
        $wrapper->appendChild($column);
    }

    public function appendSettings(SymphonyDOMElement $wrapper, MessageStack $errors)
    {
        $this->appendHandleSettings($wrapper, $errors);
        $this->appendFormatterSettings($wrapper, $errors);

        $wrapper->appendChild(Widget::Input('guid', $this['guid'], 'hidden'));
        $wrapper->appendChild(Widget::Input('type', 'text', 'hidden'));
    }

    public function appendHandleSettings(SymphonyDOMElement $wrapper, MessageStack $errors)
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

    public function appendFormatterSettings(SymphonyDOMElement $wrapper, MessageStack $errors)
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
                    ($this['formatter'] == $handle),
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
}