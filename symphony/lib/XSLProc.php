<?php

use MessageStack;
use DOMDocument;
use XSLTProcessor;
use XSLProcException;

use Embark\CMS\Structures\ParameterPool;

final class XSLProc
{
    const ERROR_XML = 1;
    const ERROR_XSL = 2;

    const DOC = 3;
    const XML = 4;

    private static $errors;

    private static $lastXML;
    private static $lastXSL;

    public static function lastXML()
    {
        return self::$lastXML;
    }

    public static function lastXSL()
    {
        return self::$lastXSL;
    }

    public static function isXSLTProcessorAvailable()
    {
        return (class_exists('XSLTProcessor'));
    }

    private static function processLibXMLerrors($type = self::ERROR_XML)
    {
        if (!(self::$errors instanceof MessageStack)) {
            self::$errors = new MessageStack();
        }

        foreach (libxml_get_errors() as $error) {
            $error->type = $type;
            self::$errors->append(null, $error);
        }

        libxml_clear_errors();
    }

    public static function tidyDocument(DOMDocument $xml)
    {
        $result = XSLProc::transform(
            $xml,
            '<?xml version="1.0" encoding="UTF-8"?>
            <xsl:stylesheet version="1.0"
              xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

            <xsl:output method="xml" indent="yes" />

            <xsl:strip-space elements="*"/>

            <xsl:template match="node() | @*">
                <xsl:copy>
                    <xsl:apply-templates select="node() | @*"/>
                </xsl:copy>
            </xsl:template>

            </xsl:stylesheet>',
            XSLProc::DOC
        );

        $result->preserveWhitespace = true;
        $result->formatOutput = true;

        return $result;
    }

    public static function flush()
    {
        if (!(self::$errors instanceof MessageStack)) {
            self::$errors = new MessageStack();
        }

        self::$errors->flush();
        self::$lastXML = self::$lastXSL = null;
    }

    public static function transform($xml, $xsl, $output = self::XML, array $parameters = array(), array $register_functions = array())
    {
        self::flush();

        self::$lastXML = $xml;
        self::$lastXSL = $xsl;
        $result = null;

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if ($xml instanceof DOMDocument) {
            $XMLDoc = $xml;
        } else {
            $XMLDoc = new DOMDocument();
            $XMLDoc->loadXML($xml);
        }

        self::processLibXMLerrors(self::ERROR_XML);

        if ($xsl instanceof DOMDocument) {
            $XSLDoc = $xsl;
        } else {
            $XSLDoc = new DOMDocument();
            $XSLDoc->loadXML($xsl);
        }

        if (!self::hasErrors() && ($XSLDoc instanceof DOMDocument) && ($XMLDoc instanceof DOMDocument)) {
            $XSLProc = new XSLTProcessor();
            $XSLProc->registerPHPFunctions();
            $XSLProc->importStyleSheet($XSLDoc);

            if (is_array($parameters) && !empty($parameters)) {
                $XSLProc->setParameter('', $parameters);
            }

            self::processLibXMLerrors(self::ERROR_XSL);

            if (!self::hasErrors()) {
                $result = $XSLProc->{'transformTo'.($output == self::XML ? 'XML' : 'Doc')}($XMLDoc);
                self::processLibXMLerrors(self::ERROR_XML);
            }
        }

        if (is_null($result) && self::hasErrors() && !isset($_GET['profiler'])) {
            //throw new exception('XSLT ERROR');
            throw new XSLProcException('Transformation Failed');
            //var_dump(self::$errors);
            //exit;
        }

        return $result;
    }

    public static function hasErrors()
    {
        return (bool) (self::$errors instanceof MessageStack && self::$errors->valid());
    }

    public static function getErrors()
    {
        return self::$errors;
    }
}
