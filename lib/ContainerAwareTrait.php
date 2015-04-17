<?php

namespace Embark\Journey;

use Pimple\Container;

trait ContainerAwareTrait
{
    /**
     * Stored instance of Pimple\Container
     * @var Container
     */
    protected $container;

    /**
     * Get or Set a container instance. Container can only be set once.
     *
     * @param  Container $container
     *  instance of container to set
     *
     * @return Container
     *  instance of Container
     *
     * @throws RuntimeException
     *  if the container is already set when setting
     */
    public function container(Container $container = null)
    {
        if (null !== $container && null !== $this->container) {
            throw new RuntimeException("Container can only be set once into ContainerAware");
        }

        if (null !== $container && null === $this->container) {
            $this->container = $container;
        }

        return $this->container;
    }
}
