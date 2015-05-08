<?php

use FilterIterator;
use DirectoryIterator;

class FieldFilterIterator extends FilterIterator
{
    public function __construct($path)
    {
        parent::__construct(new DirectoryIterator($path));
    }

    public function accept()
    {
        if ($this->isDir() == false && preg_match('/^field\..+\.php$/i', $this->getFilename())) {
            return true;
        }
        return false;
    }
}
