<?php

use TextFormatterException;
use TextFormatterIterator;

abstract class TextFormatter
{
    const NONE = 'none';

    private static $iterator;
    private static $formatters;

    abstract public function run($string);

    public static function getHandleFromFilename($filename)
    {
        return preg_replace('/.php$/i', null, $filename);
    }

    public static function load($path)
    {
        if (!is_array(self::$formatters)) {
            self::$formatters = array();
        }

        if (!file_exists($path)) {
            throw new TextFormatterException("No such Formatter '{$path}'");
        }

        if (!isset(self::$formatters[$path])) {
            self::$formatters[$path] = require_once($path);
        }

        return new self::$formatters[$path];
    }

    public static function loadFromHandle($handle)
    {
        if (!is_array(self::$formatters)) {
            self::$formatters = array();
        }

        if (!(self::$iterator instanceof TextFormatterIterator)) {
            self::$iterator = new TextFormatterIterator;
        }

        self::$iterator->rewind();

        if (in_array($handle, array_values(self::$formatters))) {
            $tmp = array_flip(self::$formatters);
            return new $tmp[$handle];
        }

        foreach (self::$iterator as $tf) {
            if (basename($tf) == "{$handle}.php") {
                return self::load($tf);
            }
        }

        throw new TextFormatterException("No such Formatter '{$handle}'");
    }
}
