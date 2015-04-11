<?php

namespace Embark\CMS\Metadata\Types;

use DOMElement;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;

class Resource implements MetadataInterface
{
    use MetadataTrait;

    public function __construct(DOMElement $xml)
    {
        $this['uri'] = urldecode($xml->ownerDocument->documentURI);
        $this['handle'] = basename($this['uri'], '.xml');
        $this['uri'] = str_replace(DOCROOT . '/', '', $this['uri']);
    }
}
