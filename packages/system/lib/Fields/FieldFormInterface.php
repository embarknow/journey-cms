<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Metadata\MetadataInterface;
use DOMElement;
use HTMLDocument;
use Exception;
use Widget;

interface FieldFormInterface extends MetadataInterface
{
    /**
     * Append stylesheets and scripts to the current page.
     *
     * @param   HTMLDocument    $page
     * @param   EntryInterface  $entry
     * @param   FieldInterface  $field
     * @param   array           $headersAppended
     *  A list of classes that have already appended their
     *  headers to the page. Add your own checks to prevent
     *  adding the same headers to a page multiple times.
     */
    public function appendPublishHeaders(HTMLDocument $page, EntryInterface $entry, FieldInterface $field, array &$headersAppended);

    /**
     * Append the field interface to the publishing form.
     *
     * @param   DOMElement      $wrapper
     * @param   FieldInterface  $field
     */
    public function appendPublishForm(DOMElement $wrapper);

    /**
     * Update the field interface to include the provided field data.
     *
     * @param   EntryInterface  $entry
     * @param   FieldInterface  $field
     * @param   mixed           $data
     */
    public function setData(EntryInterface $entry, FieldInterface $field, $data);

    /**
     * Update the field interface to show an error message.
     *
     * @param   EntryInterface  $entry
     * @param   FieldInterface  $field
     * @param   Exception       $error
     */
    public function setError(EntryInterface $entry, FieldInterface $field, Exception $error);
}
