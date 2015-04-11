<?php

namespace Embark\CMS;

class Link
{
    /**
     * The path string
     *
     * @var string
     */
    private $path = '';

    /**
     * Query Parameters array
     *
     * @var array
     */
    private $parameters = [];

    /**
     * URI Fragment string
     *
     * @var string
     */
    private $fragment = '';

    /**
     * Accepts a URI string
     *
     * @param string $link
     *  The URI string
     */
    public function __construct($link = '')
    {
        if (!is_string($link)) {
            throw new InvalidArgumentException(sprintf(
                'URI passed to constructor must be a string; received "%s"',
                (is_object($link) ? get_class($link) : gettype($link))
            ));
        }

        if (!empty($link)) {
            $this->parseLink($link);
        }
    }

    /**
     * Retrieve the path segment of the URI.
     *
     * @return string
     *  The path segment of the URI.
     */
    public function getPath()
    {
        if (empty($this->path)) {
            return '/';
        }

        return $this->path;
    }

    /**
     * Retrieve all the query parameters
     *
     * @return array
     *  The query parameters in this instance
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Check this instance has a query parameter set
     *
     * @param  string  $name
     *  The parameter name
     *
     * @return boolean
     *  True if it is set, false otherwise
     */
    public function hasParameter($name)
    {
        return array_key_exists($name, $this->paramaters);
    }

    /**
     * Retrieve a pquery parameter by name from this instance
     *
     * @param  string $name
     *  The parameter name
     *
     * @return string
     *  The parameter value
     *
     * @throws RuntimeException
     *  if the parameter is not set
     */
    public function getParameter($name)
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new RuntimeException(sprintf(
                "Query Parameter '%s' is not set.",
                $name
            ));
        }

        return $this->parameters[$name];
    }

    /**
     * Retrieve the query string of the URI.
     *
     * @return string
     *  The URI query string.
     */
    public function getQuery()
    {
        return $this->stringifyParameters();
    }

    /**
     * Retrieve the fragment segment of the URI.
     *
     * This method MUST return a string; if no fragment is present, it MUST
     * return an empty string.
     *
     * The string returned MUST omit the leading "#" character.
     *
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Create a new instance with the specified path
     *
     * @param  string $path
     *  The path to use in the new instance
     *
     * @return self
     *  A new instance with the changed path
     */
    public function withPath($path)
    {
        if ($path === $this->path) {
            return clone $this;
        }

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * Create a new instance with the specified parameter
     *
     * @param  string $name
     *  The parameter name to use in the new instance
     *
     * @param  string $value
     *  The parameter value to use in the new instance
     *
     * @return self
     *  A new instance with the changed path
     *
     * @throws InvalidArgumentException
     *  if either the name or value are not a string
     */
    public function withParameter($name, $value)
    {
        if (array_key_exists($name, $this->parameters)) {
            $existing = $this->paramaters[$name];

            if ($existing === $value) {
                return clone $this;
            }
        }

        if (!is_string($name)) {
            throw new InvalidArgumentException("Parameter name must be a string.");
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException("Parameter value must be a string.");
        }

        $new = clone $this;
        $new->parameters[$name] = $value;

        return $new;
    }

    /**
     * Create a new instance with the specified fragment
     *
     * @param  string $fragment
     *  The fragment to use in the new instance
     *
     * @return self
     *  A new instance with the changed fragment
     */
    public function withFragment($fragment)
    {
        if ($fragment === $this->fragment) {
            return clone $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;
    }

    /**
     * Parse a URI to get certain parts for this instance
     */
    private function parseLink()
    {
        $parts = parse_url($link);

        if (array_key_exists('path', $parts)) {
            $this->path = $parts->path;
        }

        if (array_key_exists('query', $parts)) {
            $this->parameters = $this->parseParameters($parts['query']);
        }

        if (array_key_exists('fragment', $parts)) {
            $this->fragment = $parts['fragment'];
        }
    }

    /**
     * Parse Query parameters
     *
     * @param  string $params
     *  Query parameters in string form
     *
     * @return array
     *  Array of parse and separated query parameters
     */
    private function parseParameters($params)
    {
        $params = explode('&', $params);
        $result = [];

        foreach ($params as $param) {
            $parts = explode('=', $param);

            $result[$parts[0]] = $parts[1];
        }

        return $result;
    }

    /**
     * Stringify query parameters from an array
     * @return string
     *  Query parameters as a string
     */
    private function stringifyParameters()
    {
        $result = '';

        foreach ($this->parameters as $key => $value) {
            $result .= $key . '=' . $value . '&';
        }

        return rtrim($result, '&');
    }

    /**
     * Return the fragment as a URI fragment string
     * @return string
     *  The fragment string
     */
    private function stringifyFragment()
    {
        return (!empty($this->fragment)
            ? '#' . $this->fragment
            : ''
        );
    }

    /**
     * Return this instance as a string
     * @return string
     *  The component link parts joined as a string
     */
    public function __toString()
    {
        $path = $this->path;
        $parameters = $this->stringifyParameters();
        $fragment = $this->stringifyFragment();

        $path .= ($parameters === ''
            ? ''
            : '?' . $parameters
        );

        $path .= ($fragment === ''
            ? ''
            : '#' . $fragment
        );

        return $path;
    }
}
