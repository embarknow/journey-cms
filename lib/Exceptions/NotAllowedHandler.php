<?php

namespace Embark\Journey\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Phly\Http\Stream;

/**
 * Handles any 'not allowed' responses from a router
 */
class NotAllowedHandler
{
    public function __invoke(ResponseInterface $response, array $methods)
    {
        $methods = implode(', ', $methods);
        $output = '';

        // Need to get an xsl template and provide string content into $output

        $response = $response->withStatus(500)
            ->withHeader('Content-type', 'text/html')
            ->withBody(new Stream('php://temp', 'r+'))
            ->write($output);

        return $response;
    }
}
