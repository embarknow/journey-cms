<?php

abstract class Page
{
    protected $_headers;

    public function __construct()
    {
        $this->_headers = array();
    }

    public function addHeaderToPage($name, $value = null)
    {
        $this->_headers[strtolower($name)] = $name . (is_null($value) ? null : ":{$value}");
    }

    public function generate()
    {
        $this->__renderHeaders();
    }

    public function headers()
    {
        return $this->_headers;
    }

    protected function __renderHeaders()
    {
        if (!is_array($this->_headers) || empty($this->_headers)) {
            return;
        }

        foreach ($this->_headers as $value) {
            header($value);
        }
    }
}
