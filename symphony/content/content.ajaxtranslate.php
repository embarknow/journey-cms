<?php

class contentAjaxTranslate extends AjaxPage
{
    public function __construct()
    {
        $this->_status = self::STATUS_OK;
        $this->addHeaderToPage('Content-Type', 'application/json');
    }

    public function handleFailedAuthorisation()
    {
        $this->_status = self::STATUS_UNAUTHORISED;
        $this->_Result = json_encode(array('status' => __('You are not authorised to access this page.')));
    }

    public function view()
    {
        $strings = $_GET;
        $new = array();
        foreach ($strings as $id => $string) {
            if ($id == 'mode' || $id == 'symphony-page') {
                continue;
            }
            $string = urldecode($string);
            $new[$string] = __($string);
        }
        $this->_Result = json_encode($new);
    }

    public function generate()
    {
        echo $this->_Result;
        exit;
    }
}
