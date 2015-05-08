<?php

use DirectoryIterator;
use FilterIterator;

class DataSourceFilterIterator extends FilterIterator
{
    public function __construct($path)
    {
        parent::__construct(new DirectoryIterator($path));
    }

    public function accept()
    {
        return (
            false === $this->isDir()
            && preg_match('/^.+\.xml$/i', $this->getFilename())
        );
    }
}
