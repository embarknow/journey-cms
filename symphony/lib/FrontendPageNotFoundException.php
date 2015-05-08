<?php

use SymphonyErrorPage;
use View;

class FrontendPageNotFoundException extends SymphonyErrorPage
{
    public function __construct(View $page = null)
    {
        if (is_null($page)) {
            $views = View::findFromType('404');
            $page = array_shift($views);
        }

        parent::__construct(
            __('The page you requested does not exist.'),
            __('Page Not Found'),
            $page,
            array('header' => 'HTTP/1.0 404 Not Found')
        );
    }
}
