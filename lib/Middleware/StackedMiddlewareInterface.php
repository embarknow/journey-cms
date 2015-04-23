<?php

namespace Embark\Journey\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface StackedMiddlewareInterface
{
    /**
     * Invoke the class as a Middleware in a MiddlewareStack
     *
     * @param  RequestInterface  $request
     *  HTTP Request object
     *
     * @param  ResponseInterface $response
     *  HTTP Response object
     *
     * @param  callable          $next
     *  Next Middleware callable in the stack
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, $next);
}
