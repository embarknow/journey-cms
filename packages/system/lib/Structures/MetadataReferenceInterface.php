<?php

namespace Embark\CMS\Structures;

use DOMElement;
use Embark\CMS\Structures\MetadataInterface;
use Exception;

interface MetadataReferenceInterface
{
    public function exists();

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
}
