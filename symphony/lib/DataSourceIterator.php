<?php

use Iterator;
use DataSourceFilterIterator;
use Extension;
use ExtensionQuery;

class DataSourceIterator implements Iterator
{
    private static $datasources;
    private $position;

    public function __construct()
    {
        $this->position = 0;

        if (!empty(self::$datasources)) {
            return;
        }

        self::clearCachedFiles();

        foreach (new DataSourceFilterIterator(DATASOURCES) as $file) {
            self::$datasources[] = $file->getPathname();
        }

        $extensions = new ExtensionQuery();
        $extensions->setFilters(array(
            ExtensionQuery::STATUS =>    Extension::STATUS_ENABLED
        ));

        foreach ($extensions as $extension) {
            if (is_dir($extension->path . '/data-sources') === false) {
                continue;
            }

            foreach (new DataSourceFilterIterator($extension->path . '/data-sources') as $file) {
                self::$datasources[] = $file->getPathname();
            }
        }
    }

    public static function clearCachedFiles()
    {
        self::$datasources = array();
    }

    public function length()
    {
        return count(self::$datasources);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return self::$datasources[$this->position]; //Datasource::loadFromPath($this->datasources[$this->position]);
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
        return isset(self::$datasources[$this->position]);
    }
}
