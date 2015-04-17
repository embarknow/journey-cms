<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;
use Phly\Http\Server;
use Embark\Journey\Exceptions\ErrorHandler;
use Embark\Journey\Exceptions\NativeExceptionHandler;
use Embark\Journey\Exceptions\XMLExceptionHandler;
use Embark\Journey\MiddlewareStack;
use Embark\Journey\Middleware\Bootstrap;

define('DOCROOT', rtrim(dirname(__FILE__), '\\/public'));
define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname($_SERVER['PHP_SELF']), '\\/'));

$container = new Container;

/**
 * The middleware stack allows components to be added to the application in a layered fashion. These middleware are allowed to affect the running and outcome of the application. For example, authentication can prevent the application from running in full.
 */
$container['middleware'] = function ($con) {
    return new MiddlewareStack;
};

// Quick and dirty middleware to output stuff while building
$container['middleware']->addMiddleware(
    function ($request, $response, $next = null) use ($container) {
        var_dump($container['environment']);

        return (
            $next
            ? $next($request, $response)
            : $response
        );
    }
);

// The router is set up based on the request object
// $container['middleware']->addMiddleware(
//     $container['router']
// );

/**
 * Bootstrap must be the last middleware added, so it is the first called off the top of the stack
 */
$container['middleware']->addMiddleware(
    new Bootstrap($container)
);

/**
 * The error handler takes any application exception and handles it gracefully
 */
$container['error-handler'] = function ($con) {
    // return new ErrorHandler(
    //     __DIR__ . '/../lib/templates',
    //     new NativeExceptionHandler,
    //     new XMLExceptionHandler
    // );
};

/**
 * The server serves the application. Simples.
 */
$container['server'] = function ($con) {
    $middleware = $con['middleware'];

    return Server::createServer(
        $middleware,
        $_SERVER,
        $_GET,
        $_POST,
        $_COOKIE,
        $_FILES
    );
};

$container['server']->listen($container['error-handler']);
