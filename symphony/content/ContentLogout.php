<?php

use HTMLDocument;
use Administration;

Class contentLogout extends HTMLDocument
{
    public function build()
    {
        $this->view();
    }

    public function view()
    {
        Administration::instance()->logout();
        redirect(URL);
    }
}
