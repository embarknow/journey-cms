<?php

use ArrayIterator;
use Cache;
use Extension;
use ExtensionQuery;
use ExtensionWithSectionsInterface;
use SectionFilterIterator;
use Profiler;
use Section;

class SectionIterator extends ArrayIterator
{
    protected static $cache;
    protected static $handles;
    protected static $objects;
    protected static $sections;

    public static function buildCache()
    {
        $cache = self::$cache = new Cache(Cache::SOURCE_CORE, 'sections');
        $handles = $cache->{'handles'};
        $sections = $cache->{'sections'};

        if (isset(self::$objects) === false) {
            self::$objects = array();
        }

        if (empty($handles) || empty($sections)) {
            $handles = $sections = [];
            $extensions = new ExtensionQuery();
            $extensions->setFilters(array(
                ExtensionQuery::STATUS =>    Extension::STATUS_ENABLED
            ));

            Profiler::begin('Discovering sections');

            foreach (new SectionFilterIterator(SECTIONS) as $file) {
                Profiler::begin('Discovered section %section');

                $path = $file->getPathName();
                $handle = basename($path, '.xml');
                $sections[$path] = true;
                $handles[$handle] = $path;

                Profiler::store('section', $handle, 'system/section');
                Profiler::store('location', $path, 'system/resource action/discovered');
                Profiler::notice('Section location cached for future use.');
                Profiler::end();
            }

            foreach ($extensions as $extension) {
                if (is_dir($extension->path . '/sections') === false) {
                    continue;
                }

                // Extension will tell us about it's own sections:
                if ($extension instanceof ExtensionWithSectionsInterface) {
                    foreach ($extension->includeSections() as $path) {
                        Profiler::begin('Discovered section %section');

                        $path = realpath($path);
                        $handle = basename($path, '.xml');
                        $sections[$path] = true;
                        $handles[$handle] = $path;

                        Profiler::store('section', $handle, 'system/section');
                        Profiler::store('location', $path, 'system/resource action/discovered');
                        Profiler::notice('Section location cached for future use.');
                        Profiler::end();
                    }
                }

                // Old style, do the work for the extension:
                else {
                    foreach (new SectionFilterIterator($extension->path . '/sections') as $file) {
                        Profiler::begin('Discovered section %section');

                        $path = $file->getPathName();
                        $handle = basename($path, '.xml');
                        $sections[$path] = true;
                        $handles[$handle] = $path;

                        Profiler::store('section', $handle, 'system/section');
                        Profiler::store('location', $path, 'system/resource action/discovered');
                        Profiler::notice('Section location cached for future use.');
                        Profiler::end();
                    }
                }
            }

            $cache->{'handles'} = $handles;
            $cache->{'sections'} = $sections;

            Profiler::end();
        }

        self::$handles = $handles;
        self::$objects = $objects;
        self::$sections = $sections;
    }

    public static function clearCachedFiles()
    {
        $cache = new Cache(Cache::SOURCE_CORE, 'sections');
        $cache->purge();

        self::$handles = array();
        self::$sections = array();
    }

    public function __construct()
    {
        if (empty(self::$sections)) {
            self::buildCache();
        }

        parent::__construct(self::$sections);
    }

    public function current()
    {
        $path = $index = parent::key();

        if (isset(self::$handles[$index])) {
            $path = self::$handles[$index];
        }

        if (isset(self::$objects[$path]) === false) {
            Profiler::begin('Loaded section %section');

            $section = Section::loadFromFile($path);
            self::$objects[$path] = $section;

            Profiler::store('section', basename($path, '.xml'), 'system/section');
            Profiler::store('location', $path, 'system/resource action/loaded');
            Profiler::end();
        }

        return self::$objects[$path];
    }

    public function offsetExists($index)
    {
        if (isset(self::$handles[$index])) {
            $index = self::$handles[$index];
        }

        return parent::offsetExists($index);
    }

    public function offsetGet($index)
    {
        $path = $index;

        if (isset(self::$handles[$index])) {
            $path = self::$handles[$index];
        }

        if (isset(self::$objects[$path]) === false) {
            Profiler::begin('Loaded section %section');

            $section = Section::loadFromFile($path);
            self::$objects[$path] = $section;

            Profiler::store('section', basename($path, '.xml'), 'system/section');
            Profiler::store('location', $path, 'system/resource action/loaded');
            Profiler::end();
        }

        return self::$objects[$path];
    }

    public function offsetSet($index, $value)
    {
        if (isset(self::$handles[$index])) {
            $index = self::$handles[$index];
        }

        self::$objects[$index] = $value;

        return parent::offsetSet($index, true);
    }

    public function offsetUnset($index)
    {
        if (isset(self::$handles[$index])) {
            $index = self::$handles[$index];

            unset(self::$handles[$index]);
        }

        if (isset(self::$objects[$index])) {
            unset(self::$objects[$index]);
        }

        return parent::offsetUnset($index);
    }
}
