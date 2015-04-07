<?php

namespace Embark\CMS\Structures;

use DOMElement;
use Embark\CMS\Structures\Resource;
use Embark\CMS\Structures\MetadataInterface;

/**
 * Trait implementing MetadataInterface
 *
 * @see MetadataInterface
 */
trait MetadataTrait
{
    /**
     * Array of metadata
     * @var array
     */
    protected $metadata = [];

    /**
     * Schema for metadata
     * @var array
     */
    private $metadataSchema = [];

    public function __clone()
    {
        foreach ($this->metadata as $key => $value) {
            if ($value instanceof MetadataInterface) {
                $this->metadata[$key] = clone $value;
            }
        }
    }

    /**
     * Gets metadata from a MetadataInterface instance and adds it to a store
     *
     * @param  MetadataInterface $object
     *  instance to get the data from
     *
     * @return void
     */
    public function fromMetadata(MetadataInterface $object)
    {
        foreach ($object->findAll() as $name => $value) {
            // Already exists:
            if (isset($this->metadata[$name])) {
                // Merge metadata:
                if (
                    $this->metadata[$name] instanceof MetadataInterface
                    && $value instanceof MetadataInterface
                ) {
                    $this->metadata[$name]->fromMetadata($value);
                }

                // Replace it:
                else {
                    $this->metadata[$name] = $value;
                }
            }

            // Add it:
            else {
                $this->metadata[$name] = $value;
            }
        }
    }

    /**
     * Get metadata from XML
     *
     * @param  DOMElement  $xml
     *  An element to create a class structure from describing metadata
     *
     * @return void
     */
    public function fromXML(DOMElement $xml)
    {
        // The default name given to unamed/numeric items:
        $itemName = 'item';

        // Check to see if an alternative name is defined:
        foreach ($this->metadataSchema as $schemaName => $schema) {
            if (isset($schema['list']) && $schema['list']) {
                $itemName = $schemaName;
                break;
            }
        }

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
            else if (
                isset($this->metadataSchema[$name]['type'])
                && $this->metadataSchema[$name]['type'] instanceof MetadataInterface
            ) {
                $value = clone $this->metadataSchema[$name]['type'];
                $value->fromXML($node);
                $value->setDefaults();
            }

            // The data is a string:
            else {
                $value = $this->valueFromXML($name, $node->nodeValue);
            }

            // Treat as an item in a list:
            if ($name === $itemName) {
                $this->metadata[] = $value;
            }

            // Normal named properties:
            else {
                $this->metadata[$name] = $value;
            }
        }
    }

    /**
     * Set defaults defined in the schema array
     *
     * @return void
     */
    public function setDefaults()
    {
        foreach ($this->metadataSchema as $name => $schema) {
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

    /**
     * Find all metadata
     * @return array
     *  yeilds an iteratable array of key => value pairs
     */
    public function findAll()
    {
        foreach ($this->metadata as $name => $value) {
            yield $name => $value;
        }
    }

    /**
     * @see ArrayAccess
     */
    public function offsetExists($name)
    {
        return isset($this->metadata[$name]);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($name)
    {
        if (false === isset($this->metadata[$name])) {
            return null;
        }

        return $this->metadata[$name];
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($name, $value)
    {
        return $this->metadata[$name] = $this->valueFromXML($name, $value);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetUnset($name)
    {
        unset($this->metadata[$name]);
    }

    /**
     * Set the schema definition for this metadata
     * @param array $schema
     *  array describing the expected schema definition that a metadata file describes
     */
    public function setSchema(array $schema)
    {
        $this->metadataSchema = $schema;
    }

    /**
     * Save metadata to an element
     *
     * @param  DOMElement $xml
     *  an element to save to
     *
     * @return void
     */
    public function toXML(DOMElement $xml)
    {
        // The default name given to unamed/numeric items:
        $itemName = 'item';

        // Check to see if an alternative name is defined:
        foreach ($this->metadataSchema as $schemaName => $schema) {
            if (isset($schema['list']) && $schema['list']) {
                $itemName = $schemaName;
                break;
            }
        }

        foreach ($this->metadata as $name => $value) {
            // Do not output resource information:
            if ($value instanceof Resource) {
                continue;
            }

            if (is_integer($name)) {
                $node = $xml->ownerDocument->createElement($itemName);
                $xml->appendChild($node);
                $name = $itemName;
            }

            else {
                $node = $xml->ownerDocument->createElement($name);
                $xml->appendChild($node);
            }

            // The data is of the type expected by default:
            if (
                isset($this->metadataSchema[$name]['type'])
                && $this->metadataSchema[$name]['type'] instanceof MetadataInterface
                && get_class($value) === get_class($this->metadataSchema[$name]['type'])
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

    /**
     * Gets a value from xml by running a MetadataValueInterface fromXML
     *
     * @param  string $name
     *  name for the value
     *
     * @param  mixed $value
     *  value for the name
     *
     * @return mixed
     *  the value returned from a fromXML function
     */
    protected function valueFromXML($name, $value)
    {
        if (
            isset($this->metadataSchema[$name]['filter'])
            && $this->metadataSchema[$name]['filter'] instanceof MetadataValueInterface
        ) {
            return $this->metadataSchema[$name]['filter']->fromXML($value);
        }

        return $value;
    }

    /**
     * Sets a value to xml by running a MetadataValueInterface toXML
     *
     * @param  string $name
     *  name for the value
     *
     * @param  mixed $value
     *  value for the name
     *
     * @return mixed
     *  the value returned from a toXML function
     */
    protected function valueToXML($name, $value)
    {
        if (
            isset($this->metadataSchema[$name]['filter'])
            && $this->metadataSchema[$name]['filter'] instanceof MetadataValueInterface
        ) {
            return $this->metadataSchema[$name]['filter']->toXML($value);
        }

        return $value;
    }
}
