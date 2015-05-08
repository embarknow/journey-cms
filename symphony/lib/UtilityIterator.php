<?php

use Iterator;
use FileTypeFilterIterator;
use Utility;

final class UtilityIterator implements Iterator
{
    private $_iterator;
    private $_length;
    private $_position;

    public function __construct()
    {
        $this->_iterator = new FileTypeFilterIterator(UTILITIES, array('xsl'));
        $this->_length = $this->_position = 0;
        foreach ($this->_iterator as $f) {
            $this->_length++;
        }
        $this->_iterator->rewind();
    }

    public function current()
    {
        $this->_current = $this->_iterator->current();
        return Utility::load($this->_current->getPathname());
    }

    public function innerIterator()
    {
        return $this->_iterator;
    }

    public function next()
    {
        $this->_position++;
        $this->_iterator->next();
    }

    public function key()
    {
        return $this->_iterator->key();
    }

    public function valid()
    {
        return $this->_iterator->valid();
    }

    public function rewind()
    {
        $this->_position = 0;
        $this->_iterator->rewind();
    }

    public function position()
    {
        return $this->_position;
    }

    public function length()
    {
        return $this->_length;
    }
}
