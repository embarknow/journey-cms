<?php

use SymphonyErrorPageHandler;

class FrontendPageNotFoundExceptionHandler extends SymphonyErrorPageHandler
{
    public static function render($e)
    {
        parent::render($e);
    }
}
