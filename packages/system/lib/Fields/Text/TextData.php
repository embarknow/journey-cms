<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Fields\DataInterface;
use Embark\CMS\Fields\Controller;
use Embark\CMS\Schemas\Schema;
use Embark\CMS\Structures\MetadataInterface;
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
class TextData implements DataInterface, MetadataInterface
{
    const TYPE = 'text';

    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'guid' => [
                'filter' =>     new Guid()
            ]
        ]);
    }

    public function createType()
    {
        return Controller::read(static::TYPE);
    }

    public function prepare(Entry $entry, $field, $new = null, $old = null)
    {
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

    public function validate(Entry $entry, $field, $data)
    {
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

    public function read(Schema $section, Entry $entry, $field)
    {
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

    public function write(Schema $section, Entry $entry, $field, $data)
    {
        $statement = Symphony::Database()->prepare(sprintf(
            'insert into `data_%s_%s`(entry_id, handle, value) VALUES(?, ?, ?)',
            $section->settings()->handle,
            $field->settings()->handle
        ));
        $statement->bindValue(1, $entry->getId(), PDO::PARAM_INT);
        $statement->bindValue(2, $data->handle, PDO::PARAM_STR);
        $statement->bindValue(3, $data->value, PDO::PARAM_STR);

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
        $wrapper->appendChild(Widget::Input('type', get_class($this), 'hidden'));
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