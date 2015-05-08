<?php

use FilterIterator;
use DirectoryIterator;

class SectionFilterIterator extends FilterIterator
{
    public function __construct($path)
    {
        parent::__construct(new DirectoryIterator(realpath($path)));
    }

    public function accept()
    {
        if ($this->isDir() == false && preg_match('/^([^.]+)\.xml$/i', $this->getFilename())) {
            return true;
        }
        return false;
    }
}
