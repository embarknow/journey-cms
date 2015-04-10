<?php

/**
 * Not sure if these services should be loaded here.
 */

use Embark\Journey\Services\Logger;

$container->register(new Logger('main'));
