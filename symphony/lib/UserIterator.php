<?php

use Iterator;
use Symphony;

class UserIterator implements Iterator
{
    private $iterator;
    private $length;
    private $position;

    public function __construct()
    {
        $this->iterator = Symphony::Database()->query("SELECT * FROM `users` ORDER BY `user_id` ASC", array(), 'UserResult');
    }

    public function current()
    {
        return $this->iterator->current();
    }

    public function innerIterator()
    {
        return $this->iterator;
    }

    public function next()
    {
        $this->iterator->next();
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function valid()
    {
        return $this->iterator->valid();
    }

    public function rewind()
    {
        $this->iterator->rewind();
    }

    public function position()
    {
        return $this->iterator->position();
    }

    public function length()
    {
        return $this->iterator->length();
    }
}
