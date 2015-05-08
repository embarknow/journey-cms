<?php

use Iterator;
use ViewFilterIterator;

class ViewIterator implements Iterator
{
    private $_iterator;
    private $_length;
    private $_position;
    private $_current;

    public function __construct($path = null, $recurse = true)
    {
        $this->_iterator = new ViewFilterIterator($path, $recurse);
        $this->_length = $this->_position = 0;
        foreach ($this->_iterator as $f) {
            $this->_length++;
        }
        $this->_iterator->getInnerIterator()->rewind();
    }

    public function current()
    {
        $path = str_replace(VIEWS, null, $this->_iterator->current()->getPathname());

        if (!($this->_current instanceof self) || $this->_current->path != $path) {
            $this->_current = View::loadFromPath($path);
        }

        return $this->_current;
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
        return $this->_iterator->getInnerIterator()->valid();
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
