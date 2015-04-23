<?php

namespace Embark\Journey\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareStackInterface
{
    /**
     * Add a callable to the Stack
     *
     * @param callable $callable
     *
     * @throws RuntimeException If The stack is locked and dequeuing
     */
    public function addMiddleware(callable $callable);

    /**
     * Call the Stack with a Request and Response
     *
     * @param  RequestInterface  $request
     * @param  ResponseInterface $response
     *
     * @return ResponseInterface
     *
     * @throws RuntimeException If The stack is empty
     */
    public function callMiddlewareStack(RequestInterface $request, ResponseInterface $response);
}
