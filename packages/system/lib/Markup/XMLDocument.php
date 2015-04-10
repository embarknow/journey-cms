<?php

namespace Embark\CMS\Markup;

use DOMDocument;
use Embark\CMS\Markup\XMLElement;

class XMLDocument extends DOMDocument
{
    protected $errors;

    protected $errorLog;

    protected $xpathInstance;

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
                if (is_numeric($name[0])) $name = "num_" . $name;

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
