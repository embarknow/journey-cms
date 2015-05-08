<?php

use Exception;
use Symphony;
use SymphonyDOMElement;

class Layout
{
    const SMALL = 'small';
    const LARGE = 'large';

    protected $child_name;
    protected $class;
    protected $layout;
    protected $page;

    public function __construct($name = 'div', $child_name = 'div')
    {
        $this->child_name = $child_name;
        $this->class = 'columns type-';
        $this->page = Symphony::Parent()->Page;
        $this->layout = $this->page->createElement($name);
    }

    public function createColumn($size)
    {
        if ($size != Layout::SMALL && $size != Layout::LARGE) {
            throw new Exception(sprintf('Invalid column size %s.', var_export($size, true)));
        }

        $column = $this->page->createElement($this->child_name);
        $column->setAttribute('class', 'column ' . $size);
        $this->layout->appendChild($column);
        $this->class .= substr($size, 0, 1);

        return $column;
    }

    public function appendTo(SymphonyDOMElement $wrapper)
    {
        $this->layout->setAttribute('class', $this->class);

        ###
        # Delegate: LayoutPreGenerate
        # Description: Allows developers to access the layout content
        #               before it is appended to the page.
        Extension::notify('LayoutPreGenerate', '/administration/', $this->layout);

        if ($wrapper->tagName == 'form') {
            $this->layout->setAttribute('id', 'layout');
        }

        $wrapper->appendChild($this->layout);
    }
}
