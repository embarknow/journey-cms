<?php

namespace Embark\CMS\Markup;

use DOMDocument;
use Exception;
use Embark\CMS\Markup\XMLElement;

/**
 * Extends the native DOMDocument adding some functionality
 */
class XMLDocument extends DOMDocument
{
    protected $errors;

    protected $errorLog;

    protected $xpathInstance;

    /**
     * Sets up the class and register extended node classes
     *
     * @param string $version
     *  xml version number as a string
     * @param string $encoding
     *  document character encoding
     */
    public function __construct($version = '1.0', $encoding = 'utf-8')
    {
        parent::__construct($version, $encoding);

        $this->registerNodeClass('DOMDocument', 'Embark\CMS\Markup\XMLDocument');
        $this->registerNodeClass('DOMElement', 'Embark\CMS\Markup\XMLElement');

        $this->preserveWhitespace = false;
        $this->formatOutput = false;
    }

    /**
     * Smart create element that checks for unusable characters
     *
     * @param  string     $name
     *  the element name
     * @param  mixed      $value
     *  the element value
     * @param  array|null $attributes
     *  anny attributes to set on the element
     *
     * @return Element
     *  the completed element
     */
    public function createElement($name, $value = null, array $attributes = null) {
        try {
            try {
                $element = parent::createElement($name);
            }

            catch (Exception $ex) {
                if (mb_check_encoding($name, 'UTF-8') === false) {
                    $name = mb_convert_encoding($name, 'UTF-8');
                }

                // Strip unprintable characters:
                $name = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $name);

                // If the $name is numeric, prepend num_ to it:
                if (is_numeric($name[0])) {
                    $name = "num_" . $name;
                }

                $element = parent::createElement($name);
            }
        }

        catch (Exception $e) {
            throw new Exception(sprintf(
                'Invalid Character Error: %s (base64)',
                base64_encode($name)
            ));
        }

        if (!is_null($value)) {
            $element->setValue($value);
        }

        if (!is_null($attributes)) {
            $element->setAttributeArray($attributes);
        }

        return $element;
    }

    /**
     * Get an instance of DOMXPath
     * @return DOMXPath
     *  an instance of DOMXPath
     */
    public function xpath()
    {
        if (null === $this->xpathInstance) {
            $this->xpathInstance = new DOMXPath($this);
        }

        return $this->xpathInstance;
    }

    /**
     * Loads an XML document from a string
     *
     * @param  string  $source
     *  The string containing the XML
     * @param  integer $options
     *  Bitwise OR of the libxml option constants
     *
     * @return boolean
     *  Returns TRUE on success or FALSE on failure
     */
    public function loadXML($source, $options = 0)
    {
        $this->flushLog();

        libxml_use_internal_errors(true);

        $result = parent::loadXML($source, $options);

        $this->processLibXMLErrors($this->errors);

        return $result;
    }

    /**
     * Processes any lib_xml_errors from load
     */
    protected function processLibXMLErrors()
    {
        foreach (libxml_get_errors() as $error) {
            $this->errors->append(null, $error);
        }

        libxml_clear_errors();
    }

    /**
     * Returns whether this document has errors
     * @return boolean
     *  true if errors occured false if not
     */
    public function hasErrors()
    {
        return (bool) ($this->errors->valid());
    }

    /**
     * Get the errors from this document
     * @return MessageStack
     *  the errors stack
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Flush the error log
     */
    public function flushLog()
    {
        $this->errors->flush();
    }

    /**
     * Output this document as a string
     *
     * @return string
     *  the string representation of this document
     */
    public function __toString()
    {
        return $this->saveXML();
    }
}
