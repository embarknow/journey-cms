<?php

namespace Embark\Journey\Metadata\Routes;

use ReflectionClass;

use FastRoute\RouteParser;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\Journey\Metadata\Routes\RouteMethodsList;
use Embark\Journey\Metadata\Routes\RouteRedirect;
use Embark\Journey\Middleware\Router;

class Route implements MetadataInterface
{
    use MetadataTrait;

    /**
     * The url path prefix for this route
     *
     * @var string
     */
    protected $prefix;

    /**
     * Set the schema for this route
     */
    public function __construct()
    {
        $this->setSchema([
            'methods' => [
                'required' => true,
                'type' => new RouteMethodsList
            ],
            'pattern' => [
                'required' => true,
                'default' => ''
            ],
            'name' => [
                'required' => true,
                'default' => ''
            ],
            'view' => [
                'required' => false,
                'default' => ''
            ],
            'redirect' => [
                'required' => false,
                'type' => new RouteRedirect
            ]
        ]);
    }

    /**
     * Invoke this route via a router
     *
     * @return [type] [description]
     */
    public function __invoke()
    {
        if (isset($this['redirect'])) {
            $this['redirect']['url'] = $this->processPatternPrefix($this['redirect']['url'], $this->prefix);

            throw new RedirectException($this);
        }


    }

    /**
     * Set the url path prefix to use for this route
     *
     * @param string $prefix
     *  the url path prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Sets an instance of the route parser
     *
     * @param RouteParser $parser
     *  a route parser instnce
     */
    public function setParser(RouteParser $parser)
    {
        $this->routeParser = $parser;
    }

    /**
     * Add this route instance to the router
     *
     * @param Router $router
     *  the router to add to
     */
    public function addToRouter(Router $router)
    {
        $this['pattern'] = $this->processPatternPrefix($this['pattern'], $this->prefix);

        foreach ($this['methods']->findAll() as $method) {
            $router->addRoute($method, $this['pattern'], $this);
        }
    }

    /**
     * Process a route pattern to replace the prefix
     *
     * @param  string $pattern
     *  a route pattern
     * @param  string $prefix
     *  a prefix to find and replace
     *
     * @return string
     *  the processed route pattern
     */
    protected function processPatternPrefix($pattern, $prefix)
    {
        $output = $this->routeParser->parse($pattern);

        foreach ($output as &$part) {
            if (is_array($part)) {
                if ($part[0] === 'prefix') {
                    $part = $prefix;
                    continue;
                }

                $part = '{' . $part[0] . ':' . $part[1] . '}';
            }
        }

        return implode($output);
    }
}
