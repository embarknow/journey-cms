<?php

namespace Embark\CMS\Fields\Upload;

use DOMElement;
use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Schemas\Schema;
use Entry;
use Field;
use General;
use Section;

class Element implements MetadataInterface
{
    use MetadataTrait;

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, Schema $section, Entry $entry)
    {
        $field = $section->findField($this['field']);

        if (false === $field) return;

        $document = $wrapper->ownerDocument;
        $data = $entry->data()->{$this['field']};

        if (isset($data->name)) {
            $element = $document->createElement($this['field']);
            $element->appendChild($document->createElement('file', $data->name, [
                'path'      => trim($data->path, '/'),
                'name'      => $data->file
            ]));
            $wrapper->appendChild($element);

            $meta = unserialize($data->meta);
            $metaXml = $document->createElement('meta');
            $element->appendChild($metaXml);

            if (false === is_array($meta)) {
                $meta = [];
            }

            $meta['size'] = General::formatFilesize($data->size);
            $meta['type'] = $data->type;

            ksort($meta);

            foreach ($meta as $key => $value) {
                if ($key == 'creation' or $key == 'type') {
                    $metaXml->setAttribute($key, $value);
                }

                else if ($key == 'size') {
                    $bits = explode(' ', $value);

                    if (count($bits) != 2) continue;

                    $metaXml->appendChild($document->createElement(
                        'size', $bits[0], array(
                            'unit'  => $bits[1]
                        )
                    ));
                }

                else if (is_array($value)) {
                    $metaXml->appendChild($document->createElement(
                        $key, null, $value
                    ));
                }

                else {
                    $metaXml->appendChild($document->createElement(
                        $key, (string)$value
                    ));
                }
            }
        }
    }
}
