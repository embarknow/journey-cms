<?php

class DocumentHeaders
{
    protected $headers;

    public function __construct(array $headers = array())
    {
        $this->headers = $headers;
    }

    public function append($name, $value = null)
    {
        $this->headers[strtolower($name)] = $name . (is_null($value) ? null : ": {$value}");
    }

    public function render()
    {
        if (!is_array($this->headers) || empty($this->headers)) {
            return;
        }

        foreach ($this->headers as $value) {
            header($value);
        }
    }

    public function headers()
    {
        return $this->headers;
    }
}
