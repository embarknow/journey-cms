<?php

use FilterIterator;
use Extension;
use ExtensionIterator;

class ExtensionQuery extends FilterIterator
{
    const STATUS = 'status';
    const TYPE = 'type';

    protected $filters;

    public function __construct()
    {
        $this->filters = [];

        parent::__construct(new ExtensionIterator());
    }

    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    public function accept()
    {
        $extension = $this->getInnerIterator()->current();

        foreach ($this->filters as $name => $value) {
            switch ($name) {
                case self::STATUS:
                    $status = Extension::status($extension->handle);

                    // Filter failed:
                    if ($status !== $value) {
                        return false;
                    }

                    break;

                case self::TYPE:
                    $types = $extension->about()->type;

                    // Must be an array:
                    if (is_array($types) === false) {
                        $types = [$types];
                    }

                    // Filter failed:
                    if (in_array($value, $types) === false) {
                        return false;
                    }

                    break;
            }
        }

        return true;
    }
}
