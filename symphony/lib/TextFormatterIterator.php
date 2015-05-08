<?php

use Iterator;

class TextFormatterIterator implements Iterator
{
    private $position;
    private $formatters;

    public function __construct()
    {
        $this->formatters = array();

        $this->formatters = array_merge(
            glob(TEXTFORMATTERS . "/*.php"),
            glob(EXTENSIONS . "/*/text-formatters/*.php")
        );

        /*
        foreach(new DirectoryIterator(EXTENSIONS) as $dir){
            if(!$dir->isDir() || $dir->isDot() || !is_dir($dir->getPathname() . '/text-formatters')) continue;

            foreach(new TextFormatterFilterIterator($dir->getPathname() . '/text-formatters') as $file){
                $this->formatters[] = $file->getPathname();
            }
        }
        */
    }

    public function length()
    {
        return count($this->formatters);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->formatters[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->formatters[$this->position]);
    }
}
