<?php

namespace Embark\CMS\Structures;

use DOMElement;
use Embark\CMS\Structures\MetadataInterface;
use Exception;

interface MetadataReferenceInterface
{
    public function fromXML(DOMElement $xml, MetadataReferenceIndex $references = null);

    public function toXML(DOMElement $xml);

    /**
     * Resolve the reference to metadata.
     *
     * @throws  Exception
     *  When the metadata cannot be loaded.
     *
     * @return  MetadataInterface
     *  A copy of the referenced metadata.
     */
    public function resolve();

    /**
     * Resolve the reference to metadata and check that the
     * result is an instance of the specified class.
     *
     * @param   string  $class
     *  The class we expect an instance of
     *
     * @throws  Exception
     *  When the metadata cannot be loaded or is not
     *  an instance of the correct class.
     *
     * @return  MetadataInterface
     *  A copy of the referenced metadata.
     */
    public function resolveInstanceOf($class);
}
