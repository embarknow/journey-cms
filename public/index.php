<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;
use Phly\Http\Server;
use Embark\Journey\Exceptions\ErrorHandler;
use Embark\Journey\Exceptions\NativeExceptionHandler;
use Embark\Journey\Exceptions\XMLExceptionHandler;
use Embark\Journey\MiddlewareStack;

$container = new Container;

/**
 * The middleware stack allows components to be added to the application in a layered fashion. These middleware are allowed to affect the running and outcome of the application. For example, authentication can prevent the application from running in full.
 */
$container['middleware'] = function ($con) {
    return new MiddlewareStack;
};

/**
 * The error handler takes any application exception and handles it gracefully
 */
$container['error-handler'] = function ($con) {
    return new ErrorHandler(
        __DIR__ . '/../lib/templates',
        new NativeExceptionHandler,
        new XMLExceptionHandler
    );
};

/**
 * The server serves the application. Simples.
 */
$container['server'] = function ($con) {
    $application = $con['middleware'];

    return Server::createServer(
        $application,
        $_SERVER,
        $_GET,
        $_POST,
        $_COOKIE,
        $_FILES
    );
};

$container['server']->listen($container['error-handler']);
