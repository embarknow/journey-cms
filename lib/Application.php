<?php

namespace Embark\Journey;

use Exception;

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

use Embark\Journey\Middleware\MiddlewareStackInterface;
use Embark\Journey\Middleware\MiddlewareStackTrait;

use Embark\Journey\Routes\RouteCollector as Router;
use Embark\Journey\Routes\Controller as RoutesController;

/**
 * neamespaces to be moved over after concept is proven
 */

/**
 * Bootstrap the application instances. This class makes sure that the application is prepared correctly based on the current HTTP request. It is a container aware class meaning it has access to the entire container for modification during the bootstrap process.
 */
class Application implements ContainerAwareInterface, MiddlewareStackInterface
{
    use ContainerAwareTrait;
    use MiddlewareStackTrait;

    /**
     * Accepts a container instance
     *
     * @param Container $container
     *  Instance of Container
     */
    public function __construct(Container $container)
    {
        $this->container($container);

        $this->setServer();
        $this->setEnvironment();
        $this->setConfiguration();
        $this->setRouter();
        $this->setErrorHandler();
        var_dump('app constructed');
    }

    /**
     * Invoke this as a server callable.
     * This runs the application when the server 'listens' to the request and response. A plain response object or a custom exception can be recieved as output.
     * Exceptions are handled correctly to return a response object, which is then passed off to the server.
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
            $router = $this->container['router'];
            $this->addMiddleware(function ($request, $response) {
                return $response;
            });
            $this->addMiddleware($router);

            $response = $this->callMiddlewareStack($request, $response);
        } catch (Exception $e) {
            // The application can be forced to quit by throwing an Exception which is caught here
            // $response = $errorHandler($request, $response, $e);
            var_dump($e);
        }

        return $response;
    }

    /**
     * Set the server to handle the HTTP stuff
     */
    protected function setServer()
    {
        $this->container['server'] = function () {
            return Server::createServer(
                $this,
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
        $this->container['router'] = function ($con) {
            $router = new Router(
                new RoutesController,
                new RouteParser,
                new RouteDataGenerator
            );

            $router->loadRoutes(
                $con['server']->request->getUri()->getPath()
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
