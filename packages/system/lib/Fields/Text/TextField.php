<?php

namespace Embark\CMS\Fields\Text;

use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Fields\Controller;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldTrait;
use Embark\CMS\Metadata\Filters\Integer;
use Context;
use DOMDocument;
use Entry;
use MessageStack;
use Symphony;
use SymphonyDOMElement;
use Widget;

/**
 * A collection of information about the field type.
 */
class TextField implements FieldInterface
{
    use FieldTrait;

    public function __construct()
    {
        $this->setSchema([
            'data' => [
                'required' =>   true,
                'type' =>       new TextData()
            ],
            'schema' => [
                'required' =>   true,
                'type' =>       new TextSchema()
            ]
        ]);

        // TODO: Figure out a better way to do this because
        // $this->fromXML is called twice when Controller::read is used

        // Load defaults from disk:
        $document = new DOMDocument();
        $document->load(Controller::locate('text'));
        $this->fromXML($document->documentElement);
    }

    public function repairEntities($value)
    {
        return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($value));
    }

    public function repairMarkup($value)
    {
        $tidy = new Tidy();
        $tidy->parseString(
            $value, array(
                'drop-font-tags'                => true,
                'drop-proprietary-attributes'   => true,
                'enclose-text'                  => true,
                'enclose-block-text'            => true,
                'hide-comments'                 => true,
                'numeric-entities'              => true,
                'output-xhtml'                  => true,
                'wrap'                          => 0,

                // HTML5 Elements:
                'new-blocklevel-tags'           => 'section nav article aside hgroup header footer figure figcaption ruby video audio canvas details datagrid summary menu',
                'new-inline-tags'               => 'time mark rt rp output progress meter',
                'new-empty-tags'                => 'wbr source keygen command'
            ), 'utf8'
        );

        return $tidy->body()->value;
    }
}
