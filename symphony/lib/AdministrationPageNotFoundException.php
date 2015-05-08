<?php

use SymphonyErrorPage;

class AdministrationPageNotFoundException extends SymphonyErrorPage
{
    public function __construct($page = null)
    {
        parent::__construct(
            __('The page you requested does not exist.'),
            __('Page Not Found'),
            $page,
            array('header' => 'HTTP/1.0 404 Not Found')
        );
    }
}
