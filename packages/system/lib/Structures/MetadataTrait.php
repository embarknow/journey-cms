<?php

namespace Embark\CMS\Structures;

use DOMElement;

trait MetadataTrait
{
    protected $metadata = [];
    private $schema = [];

    public function fromXML(DOMElement $xml)
    {
        // Give the root metadata information about where it came from:
        if ($xml === $xml->ownerDocument->documentElement) {
            $this['resource'] = new Resource($xml);
        }

        foreach ($xml->childNodes as $node) {
            if (($node instanceof DOMElement) === false) {
                continue;
            }

            $name = $node->nodeName;

            // The data has a type:
            if ($node->hasAttribute('type')) {
                $type = '\\' . $node->getAttribute('type');
                $value = new $type;
                $value->fromXML($node);
                $value->setDefaults();
            }

            // An default type has been provided:
            elseif (
                isset($this->schema[$name]['type'])
                && $this->schema[$name]['type'] instanceof MetadataInterface
            ) {
                $value = clone $this->schema[$name]['type'];
                $value->fromXML($node);
                $value->setDefaults();
            }

            // The data is a string:
            else {
                $value = $this->valueFromXML($name, $node->nodeValue);
            }

            if ('item' === $name) {
                $this->metadata[] = $value;
            } else {
                $this->metadata[$name] = $value;
            }
        }
    }

    public function setDefaults()
    {
        foreach ($this->schema as $name => $schema) {
            if (isset($this[$name])) {
                continue;
            }

            if (false === isset($schema['required'])) {
                continue;
            }

            if (true !== $schema['required']) {
                continue;
            }

            if ($schema['type'] instanceof MetadataInterface) {
                $schema['type']->setDefaults();
                $this->metadata[$name] = $schema['type'];
            } else {
                $this->metadata[$name] = $schema['default'];
            }
        }
    }

    public function findAll()
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

    public function setSchema(array $schema)
    {
        $this->schema = $schema;
    }

    public function toXML(DOMElement $xml)
    {
        foreach ($this->metadata as $name => $value) {
            // Do not output resource information:
            if ($value instanceof Resource) continue;

            if (is_integer($name)) {
                $node = $xml->ownerDocument->createElement('item');
                $xml->appendChild($node);
                $name = 'item';
            } else {
                $node = $xml->ownerDocument->createElement($name);
                $xml->appendChild($node);
            }

            // The data is of the type expected by default:
            if (
                isset($this->schema[$name]['type'])
                && $this->schema[$name]['type'] instanceof MetadataInterface
                && get_class($value) === get_class($this->schema[$name]['type'])
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
            isset($this->schema[$name]['filter'])
            && $this->schema[$name]['filter'] instanceof MetadataValueInterface
        ) {
            return $this->schema[$name]['filter']->toXML($value);
        }

        return $value;
    }

    protected function valueFromXML($name, $value)
    {
        if (
            isset($this->schema[$name]['filter'])
            && $this->schema[$name]['filter'] instanceof MetadataValueInterface
        ) {
            return $this->schema[$name]['filter']->fromXML($value);
        }

        return $value;
    }
}
