<?php

namespace Embark\CMS\Markup;

use DOMDocument;
use DOMImplementation;
use DOMDocumentType;
use Embark\CMS\Markup\XMLDocument;
use Embark\CMS\Markup\HTMLDocument;
use Manneken\Dom\DocumentType;

/**
 * Implementation overrides the DOMImplementation to allow creation of custom DOMDocument classes
 */
class Implementation extends DOMImplementation
{
    /**
     * Creates an HTMLDocument object of the specified type with its document element
     *
     * @param  string               $namespaceURI
     *  The namespace URI of the document element to create.
     * @param  string               $qualifiedName
     *  The qualified name of the document element to create.
     * @param  DOMDocumentType|null $doctype
     *  The type of document to create or null
     *
     * @return HTMLDocument
     *  A new HTMLDocument object. If namespaceURI, qualifiedName, and doctype are null, the returned HTMLDocument is empty with no document element
     */
    public function createHTMLDocument($namespaceURI = null, $qualifiedName = null, DOMDocumentType $doctype = null)
    {
        $this->document = new HTMLDocument;

        $doctype = ($doctype
            ? $doctype
            : $this->createDocumentType('html') // Defaults to HTML5
        );

        $this->workhorse($namespaceURI, $qualifiedName, $doctype);

        return $this->document;
    }

    /**
     * Creates an XMLDocument object of the specified type with its document element
     *
     * @param  string               $namespaceURI
     *  The namespace URI of the document element to create.
     * @param  string               $qualifiedName
     *  The qualified name of the document element to create.
     * @param  DOMDocumentType|null $doctype
     *  The type of document to create or null
     *
     * @return XMLDocument
     *  A new XMLDocument object. If namespaceURI, qualifiedName, and doctype are null, the returned XMLDocument is empty with no document element
     */
    public function createXMLDocument($namespaceURI = null, $qualifiedName = null, DOMDocumentType $doctype = null)
    {
        $this->document = new XMLDocument;

        $this->workhorse($namespaceURI, $qualifiedName, $doctype);

        return $this->document;
    }

    /**
     * Creates a DOMDocument object of the specified type with its document element
     *
     * @param  string               $namespaceURI
     *  The namespace URI of the document element to create.
     * @param  string               $qualifiedName
     *  The qualified name of the document element to create.
     * @param  DOMDocumentType|null $doctype
     *  The type of document to create or null
     *
     * @return DOMDocument
     *  A new DOMDocument object. If namespaceURI, qualifiedName, and doctype are null, the returned DOMDocument is empty with no document element
     */
    public function createDocument($namespaceURI = null, $qualifiedName = null, DOMDocumentType $doctype = null)
    {
        $this->document = new DOMDocument;

        $this->workhorse($namespaceURI, $qualifiedName, $doctype);

        return $this->document;
    }

    /**
     * Workhorse function delegated to by the creat functions
     *
     * @param  string               $namespaceURI
     *  The namespace URI of the document element to create.
     * @param  string               $qualifiedName
     *  The qualified name of the document element to create.
     * @param  DOMDocumentType|null $doctype
     *  The type of document to create or null
     *
     * @return XMLDocument
     *  A new XMLDocument object. If namespaceURI, qualifiedName, and doctype are null, the returned XMLDocument is empty with no document element
     */
    protected function workhorse($namespaceURI = null, $qualifiedName = null, DOMDocumentType $doctype = null)
    {
        if (null !== $doctype) {
            $this->document->appendChild($doctype);
        }

        $this->document->appendChild(
            $namespaceURI
            ? $document->createElementNS($namespaceURI, $qualifiedName)
            : $document->createElement($qualifiedName)
        );

        return $this->document;
    }
}
