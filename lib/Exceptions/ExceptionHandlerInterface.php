<?php

namespace Embark\Journey\Exceptions;

/**
 * Describes expected exception handler behaviour
 */
interface ExceptionHandlerInterface
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
    public function __invoke($e);
}
