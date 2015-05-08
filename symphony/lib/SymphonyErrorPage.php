<?php

use Exception;
use SymphonyDOMElement;

class SymphonyErrorPage extends Exception
{
    private $_heading;
    private $_message;
    private $_type;
    private $_headers;
    private $_messageObject;
    private $_help_line;

    public function __construct($message, $heading='Fatal Error', $description=null, array $headers=array())
    {
        $this->_messageObject = null;
        if ($message instanceof SymphonyDOMElement) {
            $this->_messageObject = $message;
            $message = (string)$this->_messageObject;
        }

        parent::__construct($message);

        $this->_heading = $heading;
        $this->_headers = $headers;
        $this->_description = $description;
    }

    public function getMessageObject()
    {
        return $this->_messageObject;
    }

    public function getHeading()
    {
        return $this->_heading;
    }

    public function getErrorType()
    {
        return $this->_template;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function getTemplatePath()
    {
        $template = null;

        if (file_exists(MANIFEST . '/templates/exception.symphony.xsl')) {
            $template = MANIFEST . '/templates/exception.symphony.xsl';
        } elseif (file_exists(TEMPLATES . '/exception.symphony.xsl')) {
            $template = TEMPLATES . '/exception.symphony.xsl';
        }

        return $template;
    }

    public function getHeaders()
    {
        return $this->_headers;
    }
}
