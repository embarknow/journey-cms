<?php

namespace Embark\Journey\Exceptions;

use RuntimeException;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This exception type is thrown when the requested resource is not found
 */
class NotFoundException extends RuntimeException
{
    /**
     * The name of the template file to use for this exception
     */
    const TEMPLATE = '/not-found.xsl';

    /**
     * Create a new exception with a response object
     *
     * @param  RequestInterface  $request
     *  the current Request object
     * @param  ResponseInterface $response
     *  the current Response object
     */
    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        parent::__construct();

        $this->request = $request;
        $this->response = $response;
    }
}
