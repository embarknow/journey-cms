<?php

namespace Embark\CMS\Structures;

use DOMElement;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;

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
