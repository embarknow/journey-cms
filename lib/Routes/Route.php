<?php

namespace Embark\Journey\Routes;

use FastRoute\RouteParser;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

use Embark\Journey\Routes\RouteMethodsList;
use Embark\Journey\Routes\RouteRedirect;
use Embark\Journey\Routes\View;
use Embark\Journey\Routes\RouteCollector;

class Route implements MetadataInterface
{
    use MetadataTrait;

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
                // 'type' => new View
            ],
            'redirect' => [
                'required' => false,
                'type' => new RouteRedirect
            ]
        ]);
    }

    /**
     * Call this route to be processed
     *
     * @param  RequestInterface  $request
     *  the current HTTP request
     * @param  ResponseInterface $response
     *  the current HTTP response
     * @param  array             $parameters
     *  any parameters and their values from the request URL
     *
     * @return ResponseInterface
     *  a modified HTTP response
     *
     * @throws Redirect
     *  if the route is set to redirect
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, array $parameters)
    {
        if (isset($this['redirect'])) {
            $this['redirect']['url'] = $this->processPatternPrefix($this['redirect']['url'], $this->prefix);

            // throw new RedirectException($this);
        }

        var_dump('route works', $request);die;

        return $response;
    }

    /**
     * Add this route instance to the router
     *
     * @param Router $router
     *  the router to add to
     */
    public function addToRouter(RouteCollector $router, $prefix)
    {
        $this['pattern'] = $this->processPatternPrefix($router, $this['pattern'], $prefix);

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
    protected function processPatternPrefix($router, $pattern, $prefix)
    {
        $output = $router->getRouteParser()->parse($pattern);

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
