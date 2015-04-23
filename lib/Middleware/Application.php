<?php

namespace Embark\Journey\Middleware;

use Pimple\Container;
use Phly\Http\Server;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use FastRoute\RouteParser\Std as RouteParser;
use FastRoute\DataGenerator\GroupCountBased as RouteDataGenerator;

use Embark\Journey\ContainerAwareInterface;
use Embark\Journey\ContainerAwareTrait;
use Embark\Journey\Configuration;
use Embark\Journey\Exceptions\ErrorHandler;
use Embark\Journey\Exceptions\ExceptionHandler;
use Embark\Journey\Exceptions\MetadataExceptionHandler;
use Embark\Journey\Metadata\Configuration\Controller as ConfigurationController;
use Embark\Journey\Middleware\Stack;
use Embark\Journey\Middleware\StackedMiddlewareInterface;
use Embark\Journey\Routes\RouteCollector as Router;

use Embark\Journey\Routes\Controller as RoutesController;

/**
 * neamespaces to be moved over after concept is proven
 */

/**
 * Bootstrap the application instances. This class makes sure that the application is prepared correctly based on the current HTTP request. It is a container aware class meaning it has access to the entire container for modification during the bootstrap process.
 */
class Application implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Accepts a container instance
     *
     * @param Container $container
     *  Instance of Container
     */
    public function __construct(Container $container)
    {
        $this->container($container);

        $this->setMiddlewareStack();
        $this->setServer();
        $this->setEnvironment();
        $this->setConfiguration();
        $this->setRouter();
        $this->setErrorHandler();
        var_dump('app constructed');
    }

    /**
     * Set the middleware stack for the application
     */
    protected function setMiddlewareStack()
    {
        $this->container['middleware'] = function () {
            return new Stack;
        };
        var_dump('middleware stack created');
    }

    public function addMiddlewares(array $middlewares = [])
    {
        var_dump('add middlewares');
        $stack = $this->container['middleware'];

        // $stack->addMiddleware($this);
        $router = $this->container['router'];
        $stack->addMiddleware($router);

        foreach ($middlewares as $middleware) {
            if (is_callable($middleware)) {
                $stack->addMiddleware($middleware);
            }
        }
    }

    /**
     * Set the server to handle the HTTP stuff
     */
    protected function setServer()
    {
        $this->container['server'] = function ($con) {
            $middleware = $con['middleware'];

            return Server::createServer(
                $middleware,
                $_SERVER,
                $_GET,
                $_POST,
                $_COOKIE,
                $_FILES
            );
        };
        var_dump('server set');
    }

    /**
     * Sets the application environment based on a server env variable
     */
    protected function setEnvironment()
    {
        $serverParams = $this->container['server']->request->getServerParams();
        $environment = (
            isset($serverParams['APP_ENV'])
            ? $serverParams['APP_ENV']
            : 'production'
        );

        // Wrapping this in a function ensures it won't get overwritten
        $this->container['environment'] = function () use ($environment) {
            return $environment;
        };
    }

    /**
     * Sets the configuration based on the environment
     */
    protected function setConfiguration()
    {
        $this->container['configuration'] = function ($con) {
            return new Configuration(
                $con['environment'],
                new ConfigurationController
            );
        };
    }

    /**
     * Sets up the router
     */
    protected function setRouter()
    {
        $this->container['router'] = function () {
            $router = new Router(
                new RoutesController,
                new RouteParser,
                new RouteDataGenerator
            );

            $router->loadRoutes(
                $this->container['server']->request->getUri()->getPath()
            );

            return $router;
        };
    }

    protected function setErrorHandler()
    {
        $this->container['error-handler'] = function () {
            return new ErrorHandler(
                new ExceptionHandler,
                new MetadataExceptionHandler
            );
        };
    }
}
