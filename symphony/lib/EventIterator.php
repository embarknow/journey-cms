<?php

use Iterator;
use EventFilterIterator;
use Extension;
use ExtensionQuery;

class EventIterator implements Iterator
{
    private static $events;
    private $position;

    public function __construct()
    {
        $this->position = 0;

        if (!empty(self::$events)) {
            return;
        }

        self::clearCachedFiles();

        foreach (new EventFilterIterator(EVENTS) as $file) {
            self::$events[] = $file->getPathname();
        }

        $extensions = new ExtensionQuery();
        $extensions->setFilters(array(
            ExtensionQuery::STATUS =>    Extension::STATUS_ENABLED
        ));

        foreach ($extensions as $extension) {
            if (is_dir($extension->path . '/events') === false) {
                continue;
            }

            foreach (new EventFilterIterator($extension->path . '/events') as $file) {
                self::$events[] = $file->getPathname();
            }
        }
    }

    public static function clearCachedFiles()
    {
        self::$events = array();
    }

    public function length()
    {
        return count(self::$events);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return self::$events[$this->position]; //Datasource::loadFromPath($this->events[$this->position]);
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
        return isset(self::$events[$this->position]);
    }
}
