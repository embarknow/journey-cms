<?php

namespace Embark\Journey\Middleware;

use Pimple\Container;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Embark\Journey\ContainerAwareInterface;
use Embark\Journey\ContainerAwareTrait;
use Embark\Journey\Configuration;
use Embark\Journey\Middleware\StackedMiddlewareInterface;

/**
 * neamespaces to be moved over after concept is proven
 */
use Embark\CMS\Configuration\Controller as ConfigurationController;

/**
 * Bootstrap the application instances. This class makes sure that the application is prepared correctly based on the current HTTP request. It is a container aware class meaning it has access to the entire container for modification during the bootstrap process.
 */
class Bootstrap implements ContainerAwareInterface, StackedMiddlewareInterface
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
        $this->request = $request;
        $this->response = $response;

        $this->setEnvironment();
        $this->setConfiguration();

        // Check there actually is a next middleware, or return the response
        return (
            $next
            ? $next($this->request, $this->response)
            : $this->response
        );
    }

    /**
     * Sets the application environment based on a server env variable
     */
    protected function setEnvironment()
    {
        $environment = (
            isset($this->request->getServerParams()['APP_ENV'])
            ? $this->request->getServerParams()['APP_ENV']
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
}
