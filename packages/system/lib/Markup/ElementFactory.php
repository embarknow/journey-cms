<?php

namespace Embark\CMS\Markup;

use Embark\CMS\Markup\XMLDocument;

class ElementFactory
{
    /**
     * Instance of XMLDocument
     * @var XMLDocument
     */
    protected $document;

    public function __construct(XMLDocument $document)
    {
        $this->document = $document;
    }
}
