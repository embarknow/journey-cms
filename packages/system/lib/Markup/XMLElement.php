<?php

namespace Embark\CMS\Markup;

use DOMDocumentFragment;
use DOMElement;
use DOMNode;
use DOMText;
use Embark\CMS\Markup\XMLDocument;

/**
 * The CMS extension of DOMElement
 */
class XMLElement extends DOMElement
{
    /**
     * Array of self closing element names
     *
     * @var array
     */
    protected $selfClosing = [];

    /**
     * Prepend a child node to this element
     *
     * @param  DOMNode $node
     *  the node to prepend
     */
    public function prependChild(DOMNode $node)
    {
        if (is_null($this->firstChild)) {
            $this->appendChild($node);
        }

        else {
            $this->insertBefore($node, $this->firstChild);
        }
    }

    /**
     * Set a value in this element
     *
     * @param mixed $value
     *  the value to set
     */
    public function setValue($value)
    {
        $this->removeChildNodes();

        if ($value instanceof DOMElement || $value instanceof DOMDocumentFragment) {
            $this->appendChild($value);
        }

        elseif (is_array($value) && !empty($value)) {
            foreach ($value as $v) {
                $this->appendChild($v);
            }
        }

        elseif (!is_null($value) && is_string($value)) {
            $this->appendChild(new DOMText($value));
        }
    }

    /**
     * Set an array of attributes on this element
     *
     * @param array $attributes
     *  array of attributes as key => value pairs
     */
    public function setAttributeArray(array $attributes)
    {
        if (is_array($attributes) && !empty($attributes)) {
            foreach ($attributes as $key => $val) {
                $val = utf8_encode($val);
                $this->setAttribute($key, $val);
            }
        }
    }

    /**
     * Remove all children
     */
    public function removeChildNodes()
    {
        while ($this->hasChildNodes() === true) {
            $this->removeChild($this->firstChild);
        }
    }

    /**
     * Remove this element from its parent
     */
    public function remove()
    {
        $this->parentNode->removeChild($this);
    }

    /**
     * Wrap this element with another
     *
     * @param  DOMElement $wrapper
     *  the element to wrap around this
     */
    public function wrapWith(DOMElement $wrapper)
    {
        $this->parentNode->replaceChild($wrapper, $this);
        $wrapper->appendChild($this);
    }

    /**
     * Output this element as a string
     *
     * @return string
     *  the string representation of this element
     */
    public function __toString()
    {
        $doc = new XMLDocument;
        $doc->formatOutput = true;

        $doc->importNode($this, true);

        return $doc->saveHTML();
    }
}
