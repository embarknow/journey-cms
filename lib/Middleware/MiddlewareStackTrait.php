<?php

namespace Embark\Journey\Middleware;

use RuntimeException;
use SplStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware Stack Trait
 */
trait MiddlewareStackTrait
{
    /**
     * Stack of middleware
     *
     * @var SplStack
     */
    protected $middlewareStack;

    /**
     * Middleware Stack Lock
     *
     * @var boolean
     */
    protected $middlewareLock = false;

    /**
     * Add a callable to the Stack
     *
     * @param callable $callable
     *
     * @throws RuntimeException If The stack is locked and dequeuing
     */
    public function addMiddleware(callable $callable)
    {
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
                throw new RuntimeException('Middleware must return instance of Psr\Http\Message\ResponseInterface');
            }

            return $result;
        };
    }

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
     *
     * @param  callable|null $callable
     *
     * @throws RuntimeException If The stack is created more than once
     */
    protected function createStack()
    {
        if (null !== $this->middlewareStack) {
            throw new RuntimeException('MiddlewareStack can only be created once.');
        }

        $this->middlewareStack = new SplStack;
    }
}
