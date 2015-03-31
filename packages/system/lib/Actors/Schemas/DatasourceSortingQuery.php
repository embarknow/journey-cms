<?php

namespace Embark\CMS\Actors\Schemas;

use Embark\CMS\Database\TableAliasIndex;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\Boolean;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use DOMDocument;

class DatasourceSortingQuery implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'field' => [
                'list' =>   true
            ]
        ]);
    }

    public function buildQuery(SchemaInterface $schema, TableAliasIndex $tables, array &$joins, array &$sorts)
    {
        foreach ($this->findAll() as $item) {
            if ($item instanceof FieldInterface) {
                // If this item references a field in the schema, import that field data:
                if (isset($item['schema']['guid'])) {
                    $field = $schema->findFieldByGuid($item['schema']['guid']);

                    if ($field instanceof FieldInterface) {
                        $item->fromMetadata($field);
                    }
                }

                $item['sorting']->buildQuery($schema, $item, $tables, $joins, $sorts);
            }
        }

        if (empty($sorts)) {
            $sorts[] = $tables['entries'] . '.id asc';
        }
    }

    public function createElement(DOMDocument $document)
    {
        $element = $document->createElement('sorting');

        $element->setAttribute('field', $this['field']);
        $element->setAttribute('direction', $this['direction']);

        return $element;
    }
}
