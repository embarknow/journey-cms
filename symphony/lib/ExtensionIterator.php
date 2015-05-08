<?php

use ArrayIterator;
use DirectoryIterator;
use Cache;
use Profiler;
use Symphony;
use Extension;
use ExtensionWithIncludesInterface;

class ExtensionIterator extends ArrayIterator
{
    protected static $cache;
    protected static $data;

    public static function buildCache()
    {
        $cache = self::$cache = new Cache(Cache::SOURCE_CORE, 'extensions');
        $directories = $cache->{'directories'};
        $last = $extensions = [];

        if (is_array($directories) === false) {
            Profiler::begin('Discovering extensions');

            foreach (new DirectoryIterator(EXTENSIONS) as $dir) {
                if (is_file($dir->getPathName() . '/extension.driver.php') === false) {
                    continue;
                }

                Profiler::begin('Discovered extension %extension');

                $directories[] = (object)[
                    'path' =>    $dir->getPathName(),
                    'handle' =>    $dir->getFileName()
                ];

                Profiler::store('extension', $dir->getFileName(), 'system/extension');
                Profiler::store('location', $dir->getPathName() . '/extension.driver.php', 'system/resource action/discovered');
                Profiler::end();
            }

            $cache->{'directories'} = $directories;

            Profiler::end();
        }

        Profiler::begin('Loading extensions');

        foreach ($directories as $dir) {
            Profiler::begin('Loaded extension %extension');

            try {
                $class = include_once $dir->path . '/extension.driver.php';
            } catch (Exception $error) {
                Symphony::Log()->pushExceptionToLog($error);

                Profiler::store('exception', $error->getMessage(), 'system/exeption');
                Profiler::end();

                continue;
            }

            if (Extension::Configuration()->xpath("//extension[@handle = '{$dir->handle}'][1]/@status")) {
                $status = current(Extension::Configuration()->xpath("//extension[@handle = '{$dir->handle}'][1]/@status"));
                $last = $status;
            } else {
                $status = $last;
            }

            $extensions[$dir->handle] = $extension = new $class();
            $extension->file = $dir->path . '/extension.driver.php';
            $extension->path = $dir->path;
            $extension->handle = $dir->handle;

            // The extension is enabled, does it have files to include?
            if (
                $status == Extension::STATUS_ENABLED
                && (
                    $extension instanceof ExtensionWithIncludesInterface
                )
            ) {
                $extension->includeFiles();
            }

            Profiler::store('extension', $extension->handle, 'system/extension');
            Profiler::store('class', $class, 'system/class');
            Profiler::store('location', $extension->file, 'system/extension action/loaded');
            Profiler::store('enabled', $status == Extension::STATUS_ENABLED);
            Profiler::end();
        }

        Profiler::end();

        self::$data = $extensions;
    }

    public static function clearCachedFiles()
    {
        $cache = new Cache(Cache::SOURCE_CORE, 'extensions');
        $cache->purge();
    }

    public function __construct()
    {
        if (isset(self::$data) === false) {
            self::buildCache();
        }

        parent::__construct(self::$data);
    }
}
