<?php

namespace Embark\CMS\Markup;

use Embark\CMS\Markup\XMLDocument;
use Embark\CMS\Markup\XMLElement;

class HTMLDocument extends XMLDocument
{
    /**
     * The document DTD
     * @var string
     */
    protected $dtd = 'html';

    /**
     * HTML Element node
     * @var XMLElement
     */
    protected $html;

    /**
     * Head Element node
     * @var XMLElement
     */
    protected $head;

    /**
     * Body Element node
     * @var XMLElement
     */
    protected $body;

    /**
    * These tags must always self-close.
    *
    * @var array
    */
    protected $selfClosing = array(
      'area','base','basefont','br','col','frame','hr','img','input','link','meta','param'
    );

    /**
     * Construct and prepare an html document
     *
     * @param string $version
     *  the XML version
     * @param string $encoding
     *  the document encoding
     * @param string $dtd
     *  the dtd string
     */
    public function __construct($version = '1.0', $encoding = 'utf-8', $dtd = 'html')
    {
        parent::__construct($version, $encoding);

        $this->registerNodeClass('DOMDocument', 'Embark\CMS\Markup\HTMLDocument');
        $this->registerNodeClass('DOMElement', 'Embark\CMS\Markup\XMLElement');

        $this->preserveWhitespace = false;
        $this->formatOutput = true;

        $this->setDTD($dtd);

        $this->appendChild($this->createElement('html'));
        $this->html = $this->documentElement;

        $this->head = $this->createElement('head');
        $this->html->appendChild($this->head);

        $this->body = $this->createElement('body');
        $this->html->appendChild($this->body);
    }

    /**
     * Set the document type
     *
     * @param string $dtd
     *  the document type to set
     */
    public function setDTD($dtd)
    {
        $this->dtd = $dtd;
    }

    /**
     * Set any self closing element names
     *
     * @param array $elements [description]
     */
    public function setSelfClosingElements(array $elements)
    {
        array_replace($this->selfClosing, $elements);
    }

    /**
     * Whether this node should self close
     *
     * @param XMLElement $element
     *  the element to test
     *
     * @return boolean
     *  true if the node should self close, false if not
     */
    public function isSelfClosing(XMLElement $element)
    {
        return in_array($element->nodeName, $this->selfClosing);
    }

    /**
     * Outputs the document as XHTML with self closing elements
     *
     * @param  XMLElement|null $node
     *  optional element to save
     *
     * @return string
     *  the document or optional element as a string
     */
    public function saveXHTML(XMLElement $node = null)
    {
        if (!$node) {
            $node = $this->firstChild;
        }

        $doc = new XMLDocument();
        $clone = $doc->importNode($node->cloneNode(false), true);
        $inner = '';

        if (!$this->isSelfClosing($clone)) {
            $clone->appendChild(new DOMText(''));

            if ($node->childNodes) {
                foreach ($node->childNodes as $child) {
                    $inner .= $this->saveXHTML($child);
                }
            }
        }

        $doc->appendChild($clone);
        $out = $doc->saveXML($clone);

        return ($term ? substr($out, 0, -2) . ' />' : str_replace('><', ">$inner<", $out));

    }

    /**
     * Output this element as a string
     *
     * @return string
     *  the string representation of this element
     */
    public function __toString()
    {
        return sprintf(
            "<!doctype %s>\n%s",
            $this->dtd,
            $this->saveXHTML()
        );
    }
}
