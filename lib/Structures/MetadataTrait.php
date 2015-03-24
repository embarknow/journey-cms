<?php

namespace Embark\CMS\Structures;
use DOMElement;

trait MetadataTrait {
    protected $metadata = [];
    private $defaults = [];
    private $filters = [];

    public function fromXML(DOMElement $xml = null)
    {
        if ($xml) foreach ($xml->childNodes as $node) {
            if (($node instanceof DOMElement) === false) continue;

            $name = $node->nodeName;

            // The data has a default type:
            if (
                isset($this->defaults[$name])
                && $this->defaults[$name] instanceof MetadataInterface
            ) {
                $value = $this->defaults[$name];
                $value->fromXML($node);
                $value->fromDefaults();
            }

            // The data has a type:
            else if ($node->hasAttribute('type')) {
                $type = '\\' . $node->getAttribute('type');
                $value = new $type;
                $value->fromXML($node);
                $value->fromDefaults();
            }

            // The data is a string:
            else {
                $value = $this->valueFromXML($name, $node->nodeValue);
            }

            if ('item' === $name) {
                $this->metadata[] = $value;
            }

            else {
                $this->metadata[$name] = $value;
            }
        }
    }

    public function fromDefaults()
    {
        foreach ($this->defaults as $name => $default) {
            if (isset($this[$name])) continue;

            if ($default instanceof MetadataInterface) {
                $default->fromDefaults();
            }

            $this->metadata[$name] = $default;
        }
    }

    public function getIterator()
    {
        foreach ($this->metadata as $name => $value) {
            yield $name => $value;
        }
    }

    public function offsetExists($name)
    {
        return isset($this->metadata[$name]);
    }

    public function offsetGet($name)
    {
        if (false === isset($this->metadata[$name])) return null;

        return $this->metadata[$name];
    }

    public function offsetSet($name, $value)
    {
        return $this->metadata[$name] = $this->valueFromXML($name, $value);
    }

    public function offsetUnset($name)
    {
        unset($this->metadata[$name]);
    }

    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
    }

    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    public function toXML(DOMElement $xml)
    {
        foreach ($this->metadata as $name => $value) {
            if (is_integer($name)) {
                $node = $xml->ownerDocument->createElement('item');
                $xml->appendChild($node);
            }

            else {
                $node = $xml->ownerDocument->createElement($name);
                $xml->appendChild($node);
            }

            // The data is of the type expected by default:
            if (
                isset($this->defaults[$name])
                && $this->defaults[$name] instanceof MetadataInterface
                && get_class($value) === get_class($this->defaults[$name])
            ) {
                $value->toXML($node);
            }

            // The data has a type:
            else if ($value instanceof MetadataInterface) {
                $node->setAttribute('type', get_class($value));
                $value->toXML($node);
            }

            // The data is a string:
            else {
                $value = $this->valueToXML($name, $value);
                $text = $xml->ownerDocument->createTextNode($value);
                $node->appendChild($text);
            }
        }
    }

    protected function valueToXML($name, $value)
    {
        if (
            isset($this->filters[$name])
            && $this->filters[$name] instanceof MetadataValueInterface
        ) {
            return $this->filters[$name]->toXML($value);
        }

        return $value;
    }

    protected function valueFromXML($name, $value)
    {
        if (
            isset($this->filters[$name])
            && $this->filters[$name] instanceof MetadataValueInterface
        ) {
            return $this->filters[$name]->fromXML($value);
        }

        return $value;
    }
}