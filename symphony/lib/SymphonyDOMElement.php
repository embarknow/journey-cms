<?php

use DOMElement;
use DOMNode;
use DOMDocumentFragment;
use DOMText;
use DOMDocument;

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
