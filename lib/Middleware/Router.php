<?php

namespace Embark\Journey\Middleware;

use RuntimeException;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher as DispatcherInterface;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use FastRoute\RouteCollector;

use Embark\CMS\Metadata\MetadataControllerInterface;

use Embark\Journey\Middleware\StackedMiddlewareInterface;

class Router extends RouteCollector implements StackedMiddlewareInterface
{
    /**
     * Array of loaded metadata
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * Construct the router instance
     *
     * @param RouteParser   $parser
     *  url parser for route processing
     * @param DataGenerator $generator
     *  data generator for route collection
     */
    public function __construct(MetadataControllerInterface $controller, RouteParser $parser, DataGenerator $generator)
    {
        parent::__construct($parser, $generator);

        $this->parser = $parser; // We can't access the parent's $routeParser FFS
        $this->controller = $controller;

        $this->loadMetadata();
    }

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
    public function __invoke(RequestInterface $request, ResponseInterface $response, $next = null)
    {
        $routeInfo = $this->parseGroups(
            $request->getUri()->getPath()
        );

        $this->addRoutes($routeInfo);

        unset($routeInfo);

        $dispatcher = new Dispatcher($this->getData());

        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        switch ($routeInfo[0]) {
            case DispatcherInterface::NOT_FOUND:
                    // Need to pass off to a NotFoundException
                break;
            case DispatcherInterface::METHOD_NOT_ALLOWED:
                    $allowedMethods = $routeInfo[1];
                    // Need to pass off to a NotAllowedException
                break;
            case DispatcherInterface::FOUND:
                    $handler = $routeInfo[1];
                    $parameters = $routeInfo[2];

                    $response = $handler($request, $response, $parameters);
                break;
        }

        return (
            $next
            ? $next($request, $response)
            : $response
        );
    }

    /**
     * Load the routes metadata
     */
    protected function loadMetadata()
    {
        foreach ($this->controller->findAll() as $name => $metadata) {
            $this->metadata[$name] = $metadata;
        }
    }

    /**
     * Parses the route groups metadata based on the current request path, to provide a set of routes and route prefix
     * @param  string $uriPath
     *  the current request path
     * @return array
     *  array of routes and a prefix
     */
    protected function parseGroups($uriPath)
    {
        $parts = $this->getPathParts($uriPath);
        $isRoot = empty($parts);
        $routes = null;
        $routePrefix = null;

        foreach ($this->metadata['route-groups']->findAll() as $key => $value) {
            // There are metadata generators that are not what we are looking for
            if (!is_numeric($key)) {
                continue;
            }

            // Make the prefix useable
            $prefix = trim($value['prefix'], '/');
            $prefix = (
                empty($prefix)
                ? '/'
                : $prefix
            );

            // The frontend could be very easy to match
            if ($isRoot || $prefix === '/') {
                $routes = $this->metadata['frontend'];
                $routePrefix = $value['prefix'];

                break;
            }

            // Match the prefix
            if (in_array($prefix, $parts)) {
                $routes = $this->metadata[$value['routes']];
                $routePrefix = $value['prefix'];

                break;
            }
        }

        if (null === $routes) {
            // Need to pass off to a NotFoundException using a better exception
            throw new RuntimeException(sprintf(
                "No group of routes could be matched to the path '%s'.",
                $uriPath
            ));
        }

        return [
            'prefix' => $routePrefix,
            'routes' => $routes
        ];
    }

    protected function addRoutes($routeInfo)
    {
        foreach ($routeInfo['routes']->findAll() as $key => $route) {
            if (!is_numeric($key)) {
                continue;
            }

            $pattern = $this->processPatternPrefix($route['pattern'], $routeInfo['prefix']);

            if (isset($routeInfo['route']['redirect'])) {
                // Need to pass off to a RedirectException
                // params are the nodes of the redirect
            }

            elseif (isset($routeInfo['route']['view'])) {
                $handler = $routeInfo['route']['view'];
            }
        }
    }

    /**
     * Break the current path into parts
     *
     * @param  string $uriPath
     *  the path to break
     *
     * @return array
     *  the path as parts
     */
    protected function getPathParts($uriPath)
    {
        return array_filter(explode('/', $uriPath));
    }

    /**
     * Process a route pattern to replace the prefix
     *
     * @param  string $pattern
     *  a route pattern
     * @param  string $prefix
     *  a prefix to find and replace
     *
     * @return string
     *  the processed route pattern
     */
    protected function processPatternPrefix($pattern, $prefix)
    {
        $output = $this->parser->parse($pattern);

        foreach ($output as &$part) {
            if (is_array($part)) {
                if ($part[0] === 'prefix') {
                    $part = $prefix;
                    continue;
                }

                $part = '{' . $part[0] . ':' . $part[1] . '}';
            }
        }

        return rtrim(implode($output), '/');
    }
}
