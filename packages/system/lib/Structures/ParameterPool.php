<?php

namespace Embark\CMS\Structures;

use Exception;
use Iterator;
use Embark\CMS\Structures\Parameter;

/**
 * Provides a parameter pool for xslt
 */
final class ParameterPool implements Iterator
{
    /**
     * Array of Parameter instances
     *
     * @var array
     */
    private $parameters = [];

    /**
     * Iterator position
     *
     * @var int
     */
    private $position;

    /**
     * Iterator keys
     *
     * @var array
     */
    private $keys;

    /**
     * Register an array of key => value pairs as Parameters
     *
     * @param  array  $params
     *  an array of key => value pairs
     *
     * @return void
     */
    public function register(array $params)
    {
        foreach ($params as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Sets the initial iterator position to zero
     */
    public function __construct()
    {
        $this->position = 0;
    }

    /**
     * Magically set a name and value as a Parameter
     *
     * @param string $name
     *  the name of the parameter
     *
     * @param mixed $value
     *  mixed primitive type value
     */
    public function __set($name, $value)
    {
        $this->parameters[$name] = new Parameter($name, $value);
        $this->keys = array_keys($this->parameters);
    }

    /**
     * Magically get a parameter by its key
     *
     * @param  string $name
     *  the parameter name
     *
     * @return Parameter
     *  a Parameter object instance
     *
     * @throws Exception when no parameter with the provided name exists in the context
     */
    public function __get($name)
    {
        if (!isset($this->parameters[$name])) {
            throw new Exception("No such parameter '{$name}'");
        }

        return $this->parameters[$name];
    }

    /**
     * Tests whether a parameter exists in this context
     *
     * @param  string  $name
     *  the parameter name
     *
     * @return boolean
     *  true if the parameter exists or false if not
     */
    public function __isset($name)
    {
        return (
            isset($this->parameters[$name])
            && ($this->parameters[$name] instanceof Parameter)
        );
    }

    /**
     * Remove a parameter from this context
     *
     * @param string $name
     *  the parameter name
     */
    public function __unset($name)
    {
        unset($this->parameters[$name]);
    }

    /**
     * @see Iterator
     */
    public function current()
    {
        return current($this->parameters);
    }

    /**
     * @see Iterator
     */
    public function key()
    {
        return $this->keys[$this->position];
    }

    /**
     * @see Iterator
     */
    public function next()
    {
        $this->position++;
        next($this->parameters);
    }

    /**
     * @see Iterator
     */
    public function rewind()
    {
        reset($this->parameters);
        $this->position = 0;
    }

    /**
     * @see Iterator
     */
    public function valid()
    {
        return $this->position < $this->length();
    }

    /**
     * Get the count of this context
     *
     * @return int
     *  the count of parameters in this context
     */
    public function length()
    {
        return count($this->parameters);
    }

    /**
     * Get the current position of the iterator
     *
     * @return int
     *  the iterator position
     */
    public function position()
    {
        return $this->position;
    }

    /**
     * Convert this context to an array
     *
     * @return array
     *  array of the parameters as (string) key => (string) value pairs
     */
    public function toArray()
    {
        $result = array();

        foreach ($this as $key => $parameter) {
            $result[$key] = (string)$parameter;
        }

        return $result;
    }
}
