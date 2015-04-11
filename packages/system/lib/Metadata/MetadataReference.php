<?php

namespace Embark\CMS\Metadata;

use DOMElement;
use Exception;
use ReflectionClass;
use Embark\CMS\Metadata\Resource;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataReferenceIndex;

class MetadataReference implements MetadataReferenceInterface
{
    protected $controller;
    protected $handle;
    protected $reference;
    protected $object;

    public function __construct(MetadataInterface $object = null, $controller = null, $handle = null, $reference = null)
    {
        $this->object = $object;
        $this->controller = $controller;
        $this->handle = $handle;
        $this->reference = $reference;
    }

    public function fromXML(DOMElement $xml, MetadataReferenceIndex $references = null)
    {
        $this->controller = $xml->getAttribute('controller');
        $this->handle = $xml->getAttribute('handle');
        $this->reference = $xml->getAttribute('ref');

        // Todo: remove this when sections and actors use references,
        // at the moment we have to keep it because they are overriding
        // field configuration instead of wrapping fields.
        if ($xml->childNodes->length) {
            $this->resolve()->fromXML($xml, $references);
            $this->resolve()->setDefaults();
        }
    }

    public function toXML(DOMElement $xml)
    {
        $xml->setAttribute('controller', $this->controller);
        $xml->setAttribute('handle', $this->handle);
        $xml->setAttribute('reference', $this->reference);
    }

    /**
     * Resolve the reference to metadata.
     *
     * @throws  Exception
     *  When the metadata cannot be loaded.
     *
     * @return  MetadataInterface
     *  A copy of the referenced metadata.
     */
    public function resolve()
    {
        if (isset($this->object)) {
            return $this->object;
        }

        $controller = $this->controller;
        $parent = $controller::read($this->handle);
        $index = $parent->getReferenceIndex();

        if (false === isset($index[$this->reference])) {
            throw new Exception(sprintf(
                'Unknown reference %s.',
                $this->reference
            ));
        }

        $value = clone $index[$this->reference];

        if (false === ($value instanceof MetadataInterface)) {
            throw new Exception(sprintf(
                'Type %s cannot be used as a reference as it does not implement MetadataInterface.',
                get_class($value)
            ));
        }

        $this->object = $value;

        return $value;
    }

    public function resolveInstanceOf($class)
    {
        $reflect = new ReflectionClass($class);
        $value = $this->resolve();

        if (false === $reflect->isInstance($value)) {
            throw new Exception(sprintf(
                'Could not resolve an instance of %s to an instance of %s.',
                $reflect->getName(),
                $class
            ));
        }

        return $value;
    }
}
