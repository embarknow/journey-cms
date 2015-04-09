<?php

namespace Embark\Journey\Exceptions;

use Embark\Journey\Exceptions\ExceptionHandlerInterface;

/**
 * Handles XML Exceptions
 */
class XMLExceptionHandler implements ExceptionHandlerInterface
{
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
}
