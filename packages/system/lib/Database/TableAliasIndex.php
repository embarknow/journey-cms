<?php

namespace Embark\CMS\Database;

use ArrayAccess;

class TableAliasIndex implements ArrayAccess
{
    protected $tables;

    public function __construct()
    {
        $this->tables = [];
    }

    public function next()
    {
        return 't' . count($this->tables);
    }

    public function offsetExists($table)
    {
        return isset($this->tables[$table]);
    }

    public function offsetSet($table, $alias)
    {
        if (isset($this->tables[$table])) {
            throw new OutOfBoundsException("Cannot redeclare table alias for table '{$table}'.");
        }

        return $this->tables[$table] = $alias;
    }

    public function offsetGet($table)
    {
        if (isset($this->tables[$table])) {
            return $this->tables[$table];
        }
    }

    public function offsetUnset($table)
    {
        unset($this->tables[$table]);
    }
}
