<?php

use GenericExceptionHandler;
use DOMDocument;
use XSLTProc;
use General;
use MessageStack;
use Frontend;

use Embark\CMS\Structures\ParameterPool;

class XSLProcExceptionHandler extends GenericExceptionHandler
{
    public static function render($e)
    {
        $xml = new DOMDocument('1.0', 'utf-8');
        $xml->formatOutput = true;

        $root = $xml->createElement('data');
        $xml->appendChild($root);

        $details = $xml->createElement('details', $e->getMessage());
        $details->setAttribute('type', ($e->getType() == XSLProc::ERROR_XML ? 'XML' : $e->getFile()));
        $details->setAttribute('file', General::sanitize($e->getFile()));
        $details->setAttribute('line', $e->getLine());
        $root->appendChild($details);

        $nearby_lines = self::__nearByLines($e->getLine(), $e->getFile(), $e->getType() == XSLProc::ERROR_XML, 6);

        $lines = $xml->createElement('nearby-lines');

        $markdown = "\t".$e->getMessage()."\n";
        $markdown .= "\t".$e->getFile()." line ".$e->getLine()."\n\n";

        foreach ($nearby_lines as $line_number => $string) {
            $markdown .= "\t{$string}";

            $string = trim(str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', General::sanitize($string)));
            $item = $xml->createElement('item');
            $item->setAttribute('number', $line_number + 1);
            $cdata = $xml->createCDATASection(strlen($string) == 0 ? '&nbsp;' : $string);
            $item->appendChild($cdata);
            $lines->appendChild($item);
        }
        $root->appendChild($lines);

        $element = $xml->createElement('markdown'); //, General::sanitize($markdown)));
        $element->appendChild($xml->createCDATASection($markdown));
        $root->appendChild($element);

        $processing_errors = $xml->createElement('processing-errors');

        if (XSLProc::getErrors() instanceof MessageStack) {
            foreach (XSLProc::getErrors() as $error) {
                $error->file = str_replace(WORKSPACE.'/', null, $error->file);
                $item = $xml->createElement('item', trim(General::sanitize($error->message)));
                if (strlen(trim($error->file)) == 0) {
                    $item->setAttribute('file', General::sanitize($error->file));
                }
                if (strlen(trim($error->line)) == 0) {
                    $item->setAttribute('line', $error->line);
                }
                $processing_errors->appendChild($item);
            }
        }

        $root->appendChild($processing_errors);

        if (Frontend::Parameters() instanceof ParameterPool) {
            $params = Frontend::Parameters();

            $parameters = $xml->createElement('parameters');

            foreach ($params as $key => $parameter) {
                $p = $xml->createElement('param');
                $p->setAttribute('key', $key);
                $p->setAttribute('value', (string) $parameter);

                if (is_array($parameter->value) && count($parameter->value) > 1) {
                    foreach ($parameter->value as $v) {
                        $p->appendChild(
                            $xml->createElement('item', (string) $v)
                        );
                    }
                }

                $parameters->appendChild($p);
            }

            $root->appendChild($parameters);
        }

        return parent::__transform($xml, 'exception.xslt.xsl');
    }
}
