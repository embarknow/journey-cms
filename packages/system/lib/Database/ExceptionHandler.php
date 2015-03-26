<?php

namespace Embark\CMS\Database;

use DOMDocument;
use GenericExceptionHandler;
use General;
use Symphony;

class ExceptionHandler extends GenericExceptionHandler
{
    public static function render($exception)
    {
        require_once(LIB . '/class.xslproc.php');

        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->formatOutput = true;

        $root = $xml->createElement('data');
        $xml->appendChild($root);

        $details = $xml->createElement('details');
        $details->appendChild($xml->createElement('message', General::sanitize($exception->getDatabaseErrorMessage())));

        if (!is_null($exception->getQuery())) {
            $details->appendChild($xml->createElement('query', General::sanitize($exception->getQuery())));
        }

        $root->appendChild($details);

        $trace = $xml->createElement('backtrace');

        foreach ($exception->getTrace() as $t) {
            $item = $xml->createElement('item');

            if (isset($t['file'])) {
                $item->setAttribute('file', General::sanitize($t['file']));
            }

            if (isset($t['line'])) {
                $item->setAttribute('line', $t['line']);
            }

            if (isset($t['class'])) {
                $item->setAttribute('class', General::sanitize($t['class']));
            }

            if (isset($t['type'])) {
                $item->setAttribute('type', $t['type']);
            }


            $item->setAttribute('function', General::sanitize($t['function']));

            $trace->appendChild($item);
        }

        $root->appendChild($trace);

        if (is_object(Symphony::Database()) && method_exists(Symphony::Database(), 'log')) {
            $queryLog = Symphony::Database()->log();

            if (count($queryLog) > 0) {
                $queries = $xml->createElement('query-log');

                $queryLog = array_reverse($queryLog);

                foreach ($queryLog as $query) {
                    $item = $xml->createElement('item', General::sanitize(trim($query->query)));

                    if (isset($query->time)) {
                        $item->setAttribute('time', number_format($query->time, 5));
                    }

                    $queries->appendChild($item);
                }

                $root->appendChild($queries);
            }
        }

        return parent::__transform($xml, 'exception.database.xsl');
    }
}
