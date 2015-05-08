<?php

use Entry;
use ResultIterator;

class EntryResult extends ResultIterator
{
    public $schema = array();

    public function current()
    {
        $record = parent::current();
        $entry = new Entry;

        foreach ($record as $key => $value) {
            $entry->$key = $value;
        }

        // // Load the section
        // try {
        //     $section = Section::loadFromHandle($entry->section);
        // }

        // catch (SectionException $e) {
        //     throw new EntryException('Section specified, "'.$entry->section.'", in Entry object is invalid.');
        // }

        // catch (Exception $e) {
        //     throw new EntryException('The following error occurred: ' . $e->getMessage());
        // }

        // foreach ($section->fields as $field) {
        //     if(!empty($this->schema) && !in_array($field->{'element-name'}, $this->schema)) continue;

        //     $entry->data()->{$field->{'element-name'}} = $field->loadDataFromDatabase($entry);
        // }

        return $entry;
    }

    public function setSchema(Array $schema = array())
    {
        $this->schema = array_keys($schema);
    }
}
