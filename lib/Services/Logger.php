<?php

namespace Embark\Journey\Services;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * The logger extends Monolog and allows it to be easily added to Pimple's Container
 */
class Logger extends MonoLogger implements ServiceProviderInterface
{
    /**
     * Register the logger as a service provider
     *
     * @param Container $container
     *  the container instance
     */
    public function register(Container $container)
    {
        // Need to use Metadata to configure this shizzle
        $this->pushHandler(new StreamHandler('path/to/your.log', Logger::WARNING));
        $container['logger'] = $this;
    }
}
