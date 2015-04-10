<?php

namespace Embark\CMS\Fields\Link;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Schemas\Controller;
use Embark\CMS\Schemas\Schema;
use Embark\CMS\SystemDateTime;
use DOMElement;
use DOMXPath;
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

        // if (isset($data)) {
        //  if (false === is_array($data)) {
        //      $data = [$data];
        //  }

        //  $element = $document->createElement($this['field']);
        //  $wrapper->appendChild($element);
        //  $xpath = new DOMXPath($document);
        //  $groups = array();

        //  foreach ($data as $item) {
        //      if (!isset($item->relation_id) || is_null($item->relation_id)) continue;

        //      if (!isset($groups[$item->relation_id])) {
        //          $groups[$item->relation_id] = array();
        //      }

        //      $groups[$item->relation_id][] = $item;
        //  }


        //  foreach ($groups as $relations) {
        //      foreach ($relations as $relation) {
        //          list($section_handle, $field_handle) = $relation->relation_field;

        //          $item = $xpath->query('item[@id = ' . $relation->relation_id . ']', $list)->item(0);

        //          if (is_null($item)) {
        //              $section = Controller::read($section_handle);

        //              $item = $document->createElement('item');
        //              $item->setAttribute('id', $relation->relation_id);
        //              $item->setAttribute('section', $section_handle);

        //              $element->appendChild($item);
        //          }

        //          $related_field = $section->findField($field_handle);
        //          $related_field->appendFormattedElement($item, $relation);
        //      }
        //  }
        // }
    }
}
