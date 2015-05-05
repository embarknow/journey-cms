<?php

namespace Embark\CMS\Entries;

use Embark\CMS\Schemas\Schema;
use StdClass;
use Symphony;

class Entry extends Schema implements EntryInterface
{
    protected $data;

    public function __construct(StdClass $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    public function __set($name, $value)
    {
        if (false === array_key_exists($name, $this->data)) {
            throw new Exception("Cannot set Entry::{$name}, no such property exists.");
        }

        $this->data->{$name} = $value;
    }

    public function __get($name)
    {
        if (false === array_key_exists($name, $this->data)) {
            throw new Exception("Cannot get Entry::{$name}, no such property exists.");
        }

        return $this->data->{$name};
    }

    public function __isset($name)
    {
        return isset($this->data->{$name});
    }
}