<?php

namespace Embark\CMS\Actors\Section;

use Embark\CMS\Database\ResultIterator;
use ArrayIterator;
use Entry;

class DatasourceResultIterator extends ArrayIterator
{
    protected $schema;
    protected $entries;

    public function __construct($result)
    {
        $iterator = new ResultIterator($result);
        $entries = array();

        if ($iterator->valid()) {
            foreach ($iterator as $record) {
                $entry = new Entry();

                foreach ($record as $key => $value) {
                    $entry->{$key} = $value;
                }

                $entries[$record->id] = $entry;
            }
        }

        $this->entries = $entries;

        parent::__construct($this->entries);
    }

    public function setSchema(array $schema = [])
    {
        $this->schema = array_keys($schema);
    }

    public function setData(array $data = [])
    {
        $entry_data = array();

        foreach ($this->schema as $field) {
            foreach ($data[$field] as $record) {
                if (isset($entry_data[$record->entry_id]) === false) {
                    $entry_data[$record->entry_id] = array();
                }

                if (isset($entry_data[$record->entry_id][$field]) === false) {
                    $entry_data[$record->entry_id][$field] = array();
                }

                $entry_data[$record->entry_id][$field][] = $record;
            }
        }

        foreach ($entry_data as $entry_id => $data) {
            $entry = $this->entries[$entry_id];

            if ($entry == null) {
                continue;
            }

            foreach ($data as $field => $field_data) {
                $entry->data()->{$field} = (
                    count($field_data) == 1
                        ? current($field_data)
                        : $field_data
                );
            }
        }
    }
}
