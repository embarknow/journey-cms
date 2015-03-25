<?php

namespace Embark\CMS;

use FilterIterator;
use Iterator;

class ClosureFilterIterator extends FilterIterator
{
    protected $closure;

    public function __construct(Iterator $iterator, callable $closure)
    {
        $this->closure = $closure;

        parent::__construct($iterator);
    }

    public function accept()
    {
        $closure = $this->closure;

        return $closure($this);
    }
}