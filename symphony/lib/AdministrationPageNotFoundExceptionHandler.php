<?php

use SymphonyErrorPageHandler;

class AdministrationPageNotFoundExceptionHandler extends SymphonyErrorPageHandler
{
    public static function render($e)
    {
        parent::render($e);
    }
}
