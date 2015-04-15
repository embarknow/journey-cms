<?php

namespace Embark\Journey;

use Pimple\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * Service class name
     * @var string
     */
    protected $service;

    /**
     * Service container handle
     * @var string
     */
    protected $handle;

    /**
     * Creator closure
     * @var Closure
     */
    protected $creator;

    /**
     * Prepare the service provider for use
     *
     * @param string $service
     *  A classname to instantiate
     * @param string $handle
     *  A handle for the container reference
     * @param Closure $creator
     *  An optional creator closure, must return a closure
     */
    public function __construct($service, $handle, $creator = null)
    {
        $this->service = $service; // Class to instantiate
        $this->handle = $handle; // Container handle
        $this->creator = $creator; // Optional creator closure
    }

    /**
     * Register a service provider on the container using the provided handle with either the provided service name or creator
     *
     * @param  Container $container
     *  The instance of Container
     *
     * @return void
     */
    public function register(Container $container)
    {
        if (null === $this->creator) {
            $service = $this->service;
            $container[$this->handle] new $service;
        }

        else {
            $container[$this->handle] = $this->creator;
        }
    }
}
