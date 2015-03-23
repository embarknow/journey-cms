<?php

namespace Embark\CMS\Configuration;

class Loader {
    private static $objects;

    protected $dir;
    protected $id;

    public function __construct($dir = CONF)
    {
        $this->dir = realpath($dir);
        $this->id = md5($dir);
    }

    public function __call($handle, array $param)
    {
        $id = $this->id . '.' . $handle;

        if (
            isset(self::$objects[$id]) === false
            || (self::$objects[$id] instanceof Element) === false
        ) {
            $class = __NAMESPACE__ . '\\Element';

            if (isset($param[0]) && strlen(trim($param[0])) > 0) $class = $param[0];

            self::$objects[$id] = new $class($this->dir . "/{$handle}.xml");
        }

        return self::$objects[$id];
    }

    public function save()
    {
        foreach (self::$objects as $obj) $obj->save();
    }
}