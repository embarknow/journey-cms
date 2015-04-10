<?php

namespace Embark\Journey\Exceptions;

use Embark\Journey\Exceptions\ExceptionHandlerInterface;

/**
 * Handles native Exceptions
 */
class NativeExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct()
    {
        set_exception_handler([__CLASS__, 'handler']);
    }

    /**
     * Accepts an exception instance to process
     *
     * @param  Exception $e
     *  an exception instance
     *
     * @return XMLDocument
     *  a resulting XMLDocument instance
     */
    public function __invoke($e)
    {

    }

    /**
     * The actual exception handler
     * @param  Exception  $e
     *  a native exception or derivitive thereof
     * @param  boolean $exit
     *  Whether to exit after handling
     * @return string|exit
     *  exception formatted as string data
     */
    public function handler($e, $exit = true)
    {

    }
}
