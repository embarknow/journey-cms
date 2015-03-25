<?php

namespace Embark\CMS\Fields\Upload;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMElement;
use Entry;
use Field;
use General;
use Section;

class Element implements MetadataInterface
{
    use MetadataTrait;

    public function appendElement(DOMElement $wrapper, DatasourceInterface $datasource, Section $section, Entry $entry)
    {
        $field = $section->fetchFieldByHandle($this['field']);

        if (!($field instanceof Field)) {
            return;
        }

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
                } elseif ($key == 'size') {
                    $bits = explode(' ', $value);

                    if (count($bits) != 2) {
                        continue;
                    }

                    $metaXml->appendChild($document->createElement(
                        'size', $bits[0], array(
                            'unit'  => $bits[1]
                        )
                    ));
                } elseif (is_array($value)) {
                    $metaXml->appendChild($document->createElement(
                        $key, null, $value
                    ));
                } else {
                    $metaXml->appendChild($document->createElement(
                        $key, (string)$value
                    ));
                }
            }
        }
    }
}
