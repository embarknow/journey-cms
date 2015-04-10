<?php

namespace Embark\Journey\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Phly\Http\Stream;

/**
 * Handles any 'not found' responses from a router
 */
class NotFoundHandler
{
    public function __invoke(ResponseInterface $response)
    {
        $output = 'Page Not Found'; // Temporary shit

        // Need to get an xsl template and provide string content into $output

        $response = $response->withStatus(404)
            ->withHeader('Content-type', 'text/html')
            ->withBody(new Stream('php://temp', 'r+'))
            ->write($output);

        return $response;
    }
}
