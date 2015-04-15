<?php

require_once 'class.messagestack.php';

class XMLDocument extends DOMDocument
{
    protected $errors;
    protected static $_errorLog;

    public function __construct($version = '1.0', $encoding = 'utf-8')
    {
        parent::__construct($version, $encoding);
        $this->registerNodeClass('DOMDocument', 'XMLDocument');
        $this->registerNodeClass('DOMElement', 'SymphonyDOMElement');

        $this->preserveWhitespace = false;
        $this->formatOutput = false;
        $this->errors = new MessageStack();
    }

    public function evaluate($query, DOMNode $node = null)
    {
        $xpath = new DOMXPath($this);

        if ($node) {
            return $xpath->evaluate($query, $node);
        }

        return $xpath->evaluate($query);
    }

    public function xpath($query, DOMNode $node = null)
    {
        $xpath = new DOMXPath($this);

        if ($node) {
            return $xpath->query($query, $node);
        }

        return $xpath->query($query);
    }

    public function flushLog()
    {
        $this->errors->flush();
    }

    public function loadXML($source, $options = 0)
    {
        $this->flushLog();

        libxml_use_internal_errors(true);

        $result = parent::loadXML($source, $options);

        self::processLibXMLerrors($this->errors);

        return $result;
    }

    public static function processLibXMLerrors(MessageStack $errors)
    {
        foreach (libxml_get_errors() as $error) {
            $error->type = $type; // WTF??
            $errors->append(null, $error);
        }

        libxml_clear_errors();
    }

    public function hasErrors()
    {
        return (bool) ($this->errors instanceof MessageStack && $this->errors->valid());
    }

    public function getErrors()
    {
        return $this->errors;
    }

    ##    Overloaded Methods for DOMDocument
    public function createElement($name, $value = null, array $attributes = null)
    {
        try {
            try {
                $element = parent::createElement($name);
            } catch (Exception $ex) {
                if (mb_check_encoding($name, 'UTF-8') === false) {
                    $name = mb_convert_encoding($name, 'UTF-8');
                }

                // Strip unprintable characters:
                $name = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $name);

                // If the $name is numeric, prepend num_ to it:
                if (is_numeric($name[0])) {
                    $name = "num_".$name;
                }

                $element = parent::createElement($name);
            }
        } catch (Exception $e) {
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
}

##    Convenience Methods for DOMElement
class SymphonyDOMElement extends DOMElement
{
    public function addClass($class)
    {
        $current = preg_split('%\s+%', $this->getAttribute('class'), 0, PREG_SPLIT_NO_EMPTY);
        $added = preg_split('%\s+%', $class, 0, PREG_SPLIT_NO_EMPTY);
        $current = array_merge($current, $added);
        $classes = implode(' ', $current);

        $this->setAttribute('class', $classes);
    }

    public function removeClass($class)
    {
        $classes = preg_split('%\s+%', $this->getAttribute('class'), 0, PREG_SPLIT_NO_EMPTY);
        $removed = preg_split('%\s+%', $class, 0, PREG_SPLIT_NO_EMPTY);
        $classes = array_diff($classes, $removed);
        $classes = implode(' ', $classes);

        $this->setAttribute('class', $classes);
    }

    public function prependChild(DOMNode $node)
    {
        if (is_null($this->firstChild)) {
            $this->appendChild($node);
        } else {
            $this->insertBefore($node, $this->firstChild);
        }
    }

    public function setValue($value)
    {
        $this->removeChildNodes();

        //    TODO: Possibly might need to Remove existing Children before adding..
        if ($value instanceof DOMElement || $value instanceof DOMDocumentFragment) {
            $this->appendChild($value);
        } elseif (is_array($value) && !empty($value)) {
            foreach ($value as $v) {
                $this->appendChild($v);
            }
        } elseif (!is_null($value) && is_string($value)) {
            //$this->nodeValue = $value;
            $this->appendChild(new DOMText($value));
        }
    }

    public function setAttributeArray(array $attributes)
    {
        if (is_array($attributes) && !empty($attributes)) {
            foreach ($attributes as $key => $val) {
                //    Temporary (I'd hope) ^BA
                $val = utf8_encode($val);
                $this->setAttribute($key, $val);
            }
        }
    }

    public function removeChildNodes()
    {
        while ($this->hasChildNodes() === true) {
            $this->removeChild($this->firstChild);
        }
    }

    public function remove()
    {
        $this->parentNode->removeChild($this);
    }

    public function wrapWith(DOMElement $wrapper)
    {
        $this->parentNode->replaceChild($wrapper, $this);
        $wrapper->appendChild($this);
    }

    public function __toString()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $doc->importNode($this, true);

        return $doc->saveHTML();
    }
}
