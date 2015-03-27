<?php

namespace Embark\CMS\Structures;

use Embark\CMS\Structures\Author;
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
    }
}
