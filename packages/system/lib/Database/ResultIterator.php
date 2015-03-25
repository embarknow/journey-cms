<?php

namespace Embark\CMS\Database;

use Exception;
use Iterator;
use PDO;
use PDOStatement;

class ResultIterator implements Iterator
{
    protected $result;
    protected $position;
    protected $lastPosition;
    protected $length;
    protected $current;

    public function __construct(PDOStatement $result)
    {
        $this->current = null;
        $this->result = $result;
        $this->position = 0;
        $this->lastPosition = null;
        $this->length = (integer)$result->rowCount();
    }

    public function current()
    {
        if ($this->length == 0) {
            throw new Exception('Cannot get current, no data returned.');
        }

        $this->current = $this->result->fetch(
            PDO::FETCH_OBJ,
            PDO::FETCH_ORI_ABS,
            $this->position()
        );

        return $this->current;
    }

    public function next()
    {
        $this->position++;
    }

    public function offset($offset)
    {
        $this->position = $offset;
    }

    public function position()
    {
        return $this->position;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function key()
    {
        return $this->position;
    }

    public function length()
    {
        return $this->length;
    }

    public function valid()
    {
        return $this->position < $this->length;
    }

    public function resultColumn($column)
    {
        $this->rewind();

        if ($this->valid() === false) return false;

        $result = array();

        foreach ($this as $r) {
            $result[] = $r->$column;
        }

        $this->rewind();

        return $result;
    }

    public function resultValue($key, $offset = 0)
    {
        if ($offset == 0) {
            $this->rewind();
        } else {
            $this->offset($offset);
        }

        if ($this->valid() === false) return false;

        return $this->current()->$key;
    }
}
