<?php

use FileIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use DirectoryIterator;

class ViewFilterIterator extends FilterIterator
{
    public function __construct($path = null, $recurse = true)
    {
        if (!is_null($path)) {
            $path = VIEWS . '/' . trim($path, '/');
        } else {
            $path = VIEWS;
        }

        parent::__construct(
            $recurse == true
                ?    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST)
                :    new DirectoryIterator($path)
        );
    }

    // Only return folders, and only those that have a 'X.config.xml' file within. This characterises a View.
    public function accept()
    {
        if ($this->getInnerIterator()->isDir() == false) {
            return false;
        }
        preg_match('/\/?([^\\\\\/]+)$/', $this->getInnerIterator()->getPathname(), $match); //Find the view handle

        return (is_file(sprintf('%s/%s.config.xml', $this->getInnerIterator()->getPathname(), $match[1])));
    }
}
