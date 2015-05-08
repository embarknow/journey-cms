<?php

use ArrayIterator;
use ReflectionObject;
use Extension;
use ExtensionQuery;

class FieldIterator extends ArrayIterator
{
    protected static $cache;

    public function __construct()
    {
        if (isset(self::$cache) === false) {
            $fields = array();

            $extensions = new ExtensionQuery();
            $extensions->setFilters(array(
                ExtensionQuery::TYPE =>        'Field',
                ExtensionQuery::STATUS =>    Extension::STATUS_ENABLED
            ));

            foreach ($extensions as $extension) {
                if (method_exists($extension, 'getFieldTypes') === false) {
                    continue;
                }

                foreach ($extension->getFieldTypes() as $info) {
                    $field = new $info->class();
                    $reflection = new ReflectionObject($field);

                    // Set 'type' property:
                    $field->type = preg_replace('%^field\.|\.php$%', null, basename($reflection->getFileName()));

                    $fields[$field->type] = $field;
                }
            }

            self::$cache = $fields;
        }

        parent::__construct(self::$cache);
    }
}
