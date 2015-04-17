<?php

namespace Embark\Journey;

use InvalidArgumentException;
use RuntimeException;

use Embark\CMS\Metadata\MetadataControllerInterface;

/**
 * Configuration handler for metadata based configuration files
 */
class Configuration
{
    /**
     * The current environment
     *
     * @var string
     */
    protected $environment;

    /**
     * Metadata controllers for this environment and the default configuration
     *
     * @var array
     */
    protected $controllers;

    /**
     * Array of loaded configuration metadata
     *
     * @var array
     */
    protected $loaded;

    /**
     * Array of required config handles
     *
     * @var array
     */
    protected $required = [
        'core', 'domain', 'filesystem', 'log', 'region', 'session', 'cookies'
    ];

    /**
     * Accepts an instance of a metadata controller
     *
     * @param string                      $environment
     *  the current environment
     * @param MetadataControllerInterface $controller
     *  instance of a metadata controller
     */
    public function __construct($environment, MetadataControllerInterface $defaultController)
    {
        $this->environment($environment);

        $this->controllers = [];
        $this->controllers['default'] = $defaultController;
        // $this->controllers[$environment] = $envController;
        // $this->controllers[$environment]->environment($environment); // environment environment environment

        $this->loaded = [];

        $this->loadRequired();
    }

    /**
     * Get a configuration controller by it's handle
     *
     * @param  string $handle
     *  the handle to get the controller with
     *
     * @return MetadataControllerInterface
     *  a controller instance
     */
    public function controller($handle)
    {
        if (!array_key_exists($handle, $this->controllers)) {
            throw new InvalidArgumentException(sprintf(
                "'%s' has not been registered as a configuration controller.",
                $handle
            ));
        }

        return $this->controllers[$handle];
    }

    /**
     * Get or Set the current environment for configuration
     *
     * @param  string $environment
     *  the current environment
     *
     * @return string
     *  the set environment
     */
    public function environment($environment = null)
    {
        if (null !== $environment) {
            $this->environment = $environment;
        }

        return $this->environment;
    }

    /**
     * Read a configuration file using this environment, falling back to the default
     *
     * @param  string $handle
     *  the file handle to read
     *
     * @return MetadataInterface
     *  the configuration metadata
     */
    public function read($handle)
    {
        if (!isset($this->loaded[$handle])) {
            $this->loaded[$handle] = $this->controllers['default']->read($handle);
        }

        return $this->loaded[$handle];
    }

    /**
     * Write a configuration handle to a metadata file.
     *
     * @param  string $handle
     *  the configuration handle to write
     */
    public function write($handle)
    {
        if (!isset($this->loaded[$handle])) {
            throw new InvalidArgumentException(sprintf(
                "'%s' is not loaded and available to be saved.",
                $handle
            ));
        }

        $this->controllers['default']->update(
            $this->loaded[$handle],
            $handle
        );

        return $this->loaded[$handle];
    }

    /**
     * Load all the required config handles
     */
    protected function loadRequired()
    {
        foreach ($this->required as $handle) {
            $this->read($handle);
        }
    }
}
