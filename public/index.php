<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container;

use Embark\Journey\Application;

define('DOCROOT', rtrim(dirname(__FILE__), '\\/public'));
define('DOMAIN', rtrim(rtrim($_SERVER['HTTP_HOST'], '\\/') . dirname($_SERVER['PHP_SELF']), '\\/'));

$container = new Container;
$application = new Application($container);

/**
 * Run the application, providing the error handler for fallback
 */
$container['server']->listen(
    $container['error-handler']
);
