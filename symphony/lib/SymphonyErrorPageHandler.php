<?php

use DOMDocument;
use General;
use SymphonyDOMElement;
use GenericExceptionHandler;

class SymphonyErrorPageHandler extends GenericExceptionHandler
{
    public static function render($e)
    {
        if (is_null($e->getTemplatePath())) {
            header('HTTP/1.0 500 Server Error');
            echo '<h1>Symphony Fatal Error</h1><p>'.$e->getMessage().'</p>';
            exit;
        }

        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->formatOutput = true;

        $root = $xml->createElement('data');
        $xml->appendChild($root);

        $root->appendChild($xml->createElement('heading', General::sanitize($e->getHeading())));
        $root->appendChild($xml->createElement('message', General::sanitize(
            $e->getMessageObject() instanceof SymphonyDOMElement ? (string)$e->getMessageObject() : trim($e->getMessage())
        )));
        if (!is_null($e->getDescription())) {
            $root->appendChild($xml->createElement('description', General::sanitize($e->getDescription())));
        }

        header('HTTP/1.0 500 Server Error');
        header('Content-Type: text/html; charset=UTF-8');
        header('Symphony-Error-Type: ' . $e->getErrorType());

        foreach ($e->getHeaders() as $header) {
            header($header);
        }

        $output = parent::__transform($xml, basename($e->getTemplatePath()));

        header(sprintf('Content-Length: %d', strlen($output)));
        echo $output;

        exit;
    }
}
