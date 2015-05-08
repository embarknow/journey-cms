<?php

namespace Embark\CMS\Database;

use Exception;

/**
 * Database Exception
 */
class Exception extends Exception
{
    /**
     * The error thrown by the database query
     * @var array
     */
    private $error;

    /**
     * Construct this exception object
     * @param string     $message
     *  the error message
     * @param array|null $error
     *  the error array if any is available
     */
    public function __construct($message, array $error = null)
    {
        parent::__construct($message);

        $this->error = $error;
    }

    /**
     * Get the query
     * @return string
     *  the SQL query that errored
     */
    public function getQuery()
    {
        return (
            isset($this->error['query'])
                ? $this->error['query']
                : null
        );
    }

    /**
     * The database error message
     * @return string
     *  the error message from PDO, or the provided message
     */
    public function getDatabaseErrorMessage()
    {
        return (
            isset($this->error['message'])
                ? $this->error['message']
                : $this->getMessage()
        );
    }

    /**
     * Get the error code
     * @return int
     *  the error code provided by PDO
     */
    public function getDatabaseErrorCode()
    {
        return (
            isset($this->error['code'])
                ? $this->error['code']
                : null
        );
    }
}
