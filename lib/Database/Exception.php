<?php

namespace Embark\CMS\Database;

class Exception extends \Exception
{
    private $error;

    public function __construct($message, array $error = null)
    {
        parent::__construct($message);

        $this->error = $error;
    }

    public function getQuery()
    {
        return (
            isset($this->error['query'])
                ? $this->error['query']
                : null
        );
    }

    public function getDatabaseErrorMessage()
    {
        return (
            isset($this->error['message'])
                ? $this->error['message']
                : $this->getMessage()
        );
    }

    public function getDatabaseErrorCode()
    {
        return (
            isset($this->error['code'])
                ? $this->error['code']
                : null
        );
    }
}