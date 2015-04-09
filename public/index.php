<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;
use Phly\Http\Server;
use Embark\Journey\Exceptions\ErrorHandler;
use Embark\Journey\Exceptions\NativeExceptionHandler;
use Embark\Journey\Exceptions\XMLExceptionHandler;
use Embark\Journey\MiddlewareStack;

$container = new Container;

$container['middleware'] = function ($con) {
    return new MiddlewareStack;
};

$container['error-handler'] = function ($con) {
    return new ErrorHandler(
        __DIR__ . '/../lib/templates',
        new NativeExceptionHandler,
        new XMLExceptionHandler
    );
};

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
