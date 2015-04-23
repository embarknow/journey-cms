<?php

namespace Embark\Journey\Middleware;

use RuntimeException;
use SplStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware Stack
 */
class Stack
{
    /**
     * Stack of middleware
     * @var SplStack
     */
    protected $middlewareStack;

    /**
     * Middleware Stack Lock
     * @var boolean
     */
    protected $middlewareLock = false;

    /**
     * Add a callable to the Stack
     * @param callable $callable
     * @throws RuntimeException If The stack is locked and dequeuing
     */
    public function addMiddleware(callable $callable)
    {
        var_dump('middleware added');
        if ($this->middlewareLock) {
            throw new RuntimeException('Middleware can’t be added once the stack is dequeuing');
        }

        if (null === $this->middlewareStack) {
            $this->createStack();
        }

        $next = null;

        try {
            $next = $this->middlewareStack->top(); // Gets last added middleware from SplStack;
        } catch (RuntimeException $e) {
            // Ignore it
        }

        $this->middlewareStack[] = function (RequestInterface $request, ResponseInterface $response) use ($next, $callable) {
            $result = $callable($request, $response, $next);

            if ($result instanceof ResponseInterface === false) {
                throw new RuntimeException('Middleware must return instance of \Psr\Http\Message\ResponseInterface');
            }

            return $result;
        };
    }

    /**
     * Call the Stack with a Request and Response
     * @param  RequestInterface  $request
     * @param  ResponseInterface $response
     * @return ResponseInterface
     * @throws RuntimeException If The stack is empty
     */
    public function callMiddlewareStack(RequestInterface $request, ResponseInterface $response)
    {
        if (null === $this->middlewareStack) {
            $this->createStack();
        }

        var_dump('actually called');

        $first = null;

        try {
            $first = $this->middlewareStack->top(); // throws RuntimeException
        } catch (RuntimeException $e) {
            // Ignore it
        }

        if (null !== $first) {
            $this->middlewareLock = true;
            $response = $first($request, $response);
            $this->middlewareLock = false;
        }

        return $response;
    }

    /**
     * Create a Middleware Stack instance
     * @param  callable|null $callable
     * @throws RuntimeException If The stack is created more than once
     */
    protected function createStack()
    {
        if (null !== $this->middlewareStack) {
            throw new RuntimeException('MiddlewareStack can only be created once.');
        }

        $this->middlewareStack = new SplStack;
        $this->middlewareStack[] = function (RequestInterface $request, ResponseInterface $response) {
            return $response;
        };
    }

    /**
     * Invoke this as a middleware callable
     *
     * @param  RequestInterface  $request
     *  The current request object
     * @param  ResponseInterface $response
     *  the current response object
     * @param  callable          $errorHandler
     *  an error handler to catch any exception errors
     *
     * @return ResponseInterface
     *  the modified response object
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, $errorHandler)
    {
        try {
            var_dump('stack invoked');
            $response = $this->callMiddlewareStack($request, $response);
        } catch (Exception $e) {
            // The application can be forced to quit by throwing an Exception which is caught here
            $response = $errorHandler($request, $response, $e);
        }

        return $response;
    }
}
