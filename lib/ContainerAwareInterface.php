<?php

namespace Embark\Journey;

use Pimple\Container;

interface ContainerAwareInterface
{
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
    public function container(Container $container = null);
}
