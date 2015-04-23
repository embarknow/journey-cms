<?php

namespace Embark\Journey\Exceptions;

use RuntimeException;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This exception type is thrown when an HTTP method is not allowed for the requested resource
 */
class NotAllowedException extends RuntimeException
{
    /**
     * The name of the template file to use for this exception
     */
    const TEMPLATE = '/not-allowed.xsl';

    /**
     * An array of allowed methods
     *
     * @var array
     */
    protected $allowedMethods;

    /**
     * Create a new exception with a response object
     *
     * @param  RequestInterface  $request
     *  the current Request object
     * @param  ResponseInterface $response
     *  the current Response object
     * @param array              $allowedMethods
     *  an array of allowed methods
     */
    public function __construct(RequestInterface $request, ResponseInterface $response, array $allowedMethods)
    {
        parent::__construct();

        $this->request = $request;
        $this->response = $response;
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * Get the allowed methods for this exception
     *
     * @return array
     *  an array of allowed methods for this exception
     */
    public function getALlowedMethods()
    {
        return $this->allowedMethods;
    }
}
