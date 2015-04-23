<?php

namespace Embark\Journey\Routes;

use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher as DispatcherInterface;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\Journey\Middleware\StackedMiddlewareInterface;

use Embark\Journey\Exceptions\NotFoundException;
use Embark\Journey\Exceptions\NotAllowedException;

/**
 * An duplicate of FastRoute\RouteCollector but with protected properties and metadata functionality
 */
class RouteCollector implements StackedMiddlewareInterface
{
    /**
     * Metadata controller
     *
     * @var MetadataControllerInterface
     */
    protected $controller;

    /**
     * Loaded metadata
     *
     * @var array
     */
    protected $metadata;

    /**
     * Instance of the route parser
     *
     * @var RouteParser
     */
    protected $routeParser;

    /**
     * Instance of the data generator
     *
     * @var DataGenerator
     */
    protected $dataGenerator;

    /**
     * Constructs a route collector.
     *
     * @param MetadataControllerInterface $controller
     *  a controller for loading route metadata
     * @param RouteParser                 $routeParser
     *  a route parser to parse parameters within route strings
     * @param DataGenerator               $dataGenerator
     *  a generator to collecte and provide the route data
     */
    public function __construct(MetadataControllerInterface $controller, RouteParser $routeParser, DataGenerator $dataGenerator)
    {
        $this->controller = $controller;
        $this->routeParser = $routeParser;
        $this->dataGenerator = $dataGenerator;

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
    public function __invoke(RequestInterface $request, ResponseInterface $response, $next)
    {
        $dispatcher = new Dispatcher($this->getData());
        $routeInfo = $dispatcher->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        switch ($routeInfo[0]) {
            case DispatcherInterface::NOT_FOUND:
                // Need to pass off to a NotFoundException
                throw new NotFoundException($request, $response);
                break;
            case DispatcherInterface::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // Need to pass off to a NotAllowedException
                throw new NotAllowedException($request, $response, $allowedMethods);
                break;
            case DispatcherInterface::FOUND:
                $handler = $routeInfo[1];
                $parameters = $routeInfo[2];

                $response = $handler($request, $response, $parameters);
                break;
        }

        return $next($request, $response);
    }

    /**
     * Adds a route to the collection.
     *
     * The syntax used in the $route string depends on the used route parser.
     *
     * @param string|string[] $httpMethod
     * @param string $route
     * @param mixed  $handler
     */
    public function addRoute($httpMethod, $route, $handler)
    {
        $routeData = $this->routeParser->parse($route);

        foreach ((array) $httpMethod as $method) {
            $this->dataGenerator->addRoute($method, $routeData, $handler);
        }
    }

    /**
     * Returns the collected route data, as provided by the data generator.
     *
     * @return array
     */
    public function getData()
    {
        return $this->dataGenerator->getData();
    }

    /**
     * Access the stored route parser
     *
     * @return RouteParser
     *  the stored route parser instance
     */
    public function getRouteParser()
    {
        return $this->routeParser;
    }

    /**
     * Load the routes metadata
     */
    protected function loadMetadata()
    {
        $this->metadata = $this->controller->read('route-groups');
    }

    /**
     * Load routes from metadata into this collector
     *
     * @param  string $requestUri
     *  the uri path of the current request
     */
    public function loadRoutes($requestUri)
    {
        $parts = $this->getPathParts($requestUri);
        if (empty($parts)) {
            $parts[] = '/';
        }

        foreach ($this->metadata->findInstancesOf('Embark\Journey\Routes\RouteGroupItem') as $item) {
            if (is_string($item['routes'])) {
                continue;
            }

            // Match the prefix
            if (in_array($item['prefix'], $parts)) {
                $routes = $item['routes']->resolve();
                $this->addRoutes($routes, $item['prefix']);

                break;
            }
        }
    }

    /**
     * Add routes to this collector
     *
     * @param MetadataInterface $routes
     *  a metadata document describing routes
     * @param string $prefix
     *  a route prefix to parse routes with
     */
    protected function addRoutes($routes, $prefix)
    {
        foreach ($routes->findAll() as $key => $route) {
            if (!is_numeric($key)) {
                continue;
            }

            $route->addToRouter($this, $prefix);
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
        $parts = array_filter(explode('/', $uriPath));

        foreach ($parts as &$item) {
            if (!empty($item)) {
                $item =  '/' . $item;
            }
        }

        return $parts;
    }
}
