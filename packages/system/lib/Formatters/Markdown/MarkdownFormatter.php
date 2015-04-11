<?php

namespace Embark\CMS\Formatters\Markdown;

use Embark\CMS\Formatters\Controller as FormatterController;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Filters\Boolean;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use DOMDocument;
use Tidy;

class MarkdownFormatter implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'inline' => [
                'filter' =>     new Boolean()
            ]
        ]);

        // TODO: Figure out a better way to do this because
        // $this->fromXML is called twice when Controller::read is used

        // Load defaults from disk:
        $document = new DOMDocument();
        $document->load(FormatterController::locate('markdown'));
        $this->fromXML($document->documentElement);
    }

    public function format($input)
    {
        $environment = Environment::createCommonMarkEnvironment();
        $parser = new DocParser($environment);
        $htmlRenderer = new HtmlRenderer($environment);
        $ast = $parser->parse($input);

        if ($this['inline']) {
            if (false === $ast->hasChildren()) {
                return null;
            }

            $output = $htmlRenderer->renderInlines($ast->getLastChild()->getInlines());
        }

        else {
            $output = $htmlRenderer->renderBlock($ast);
        }

        $output = $this->repairEntities($output);
        $document = new DOMDocument();
        $fragment = $document->createDocumentFragment();

        try {
            $fragment->appendXML($output);
        }

        catch (\Exception $e) {
            $output = $this->repairMarkup($output);
            $fragment->appendXML($output);
        }

        try {
            $document->appendChild($fragment);
        }

        catch (\Exception $e) {
            // Only get 'Document Fragment is empty' errors here.
        }

        return trim($output);
    }

    public function repairEntities($input)
    {
        return preg_replace('/&(?!(#[0-9]+|#x[0-9a-f]+|amp|lt|gt);)/i', '&amp;', trim($input));
    }

    public function repairMarkup($input)
    {
        $tidy = new Tidy();
        $tidy->parseString(
            $input, [
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
            ], 'utf8'
        );

        $body = $tidy->body();
        $output = '';

        if ($this['inline']) {
            if (isset($body->child[0])) {
                foreach ($body->child[0]->child as $child) {
                    $output .= $child->value;
                }
            }
        }

        else foreach ($body->child as $child) {
            $output .= $child->value;
        }

        return $output;
    }
}
