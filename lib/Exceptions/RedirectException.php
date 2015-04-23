<?php

namespace Embark\Journey\Exceptions;

use RuntimeException;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This exception type is thrown when a route or application logic  requests to be redirected
 */
class RedirectException extends RuntimeException
{

}
