<?php

use FilterIterator;
use DirectoryIterator;

class FileTypeFilterIterator extends FilterIterator
{
    protected $_extensions;

    public function __construct($path, array $extensions)
    {
        $this->_extensions = array_map('strtolower', $extensions);
        parent::__construct(new DirectoryIterator($path));
    }

    public function accept()
    {
        return (in_array(strtolower(pathinfo($this->current(), PATHINFO_EXTENSION)), $this->_extensions) ? true : false);
    }
}
