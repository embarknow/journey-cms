<?php

namespace Embark\CMS\Actors\Schemas;

use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Database\Exception as DatabaseException;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\Controller as SchemaController;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Metadata\Types\Resource;
use Embark\CMS\Metadata\Types\Pagination;
use Embark\CMS\Metadata\Types\QueryOptions;
use Embark\CMS\SystemDateTime;
use DOMElement;
use Entry;
use PDO;
use Section;
use Symphony;
use Widget;

class Datasource implements DatasourceInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'pagination' => [
                'type' =>       new DatasourcePaginationQuery()
            ],
            'sorting' => [
                'type' =>       new DatasourceSortingQuery()
            ],
            'elements' => [
                'type' =>       new DatasourceOutputElements()
            ],
            'parameters' => [
                'type' =>       new DatasourceOutputParameters()
            ]
        ]);
    }

    public function canExecute()
    {
        return true;
    }

    public function getType()
    {
        return __('Section Datasource');
    }

    public function createForm()
    {
        return new DatasourceForm($this);
    }

    public function createRenderer()
    {
        $schema = SchemaController::read($this['schema']);

        return new DatasourceRenderer($this, $schema);
    }

    public function appendColumns(DOMElement $wrapper)
    {
        $section = Section::loadFromHandle($this['section']);

        // Name:
        $wrapper->appendChild(Widget::TableData(Widget::Anchor(
            $this['name'],
            ADMIN_URL . "/blueprints/actors/edit/{$this['resource']['handle']}/"
        )));

        // Source:
        if ($section instanceof Section) {
            $wrapper->appendChild(Widget::TableData(Widget::Anchor(
                $section->name,
                ADMIN_URL . "/blueprints/sections/edit/{$section->handle}/"
            )));
        } else {
            $wrapper->appendChild(Widget::TableData(__('None'), [
                'class' =>  'inactive'
            ]));
        }

        // Type:
        $wrapper->appendChild(Widget::TableData($this->getType()));
    }

    public function findEntries()
    {
        $query = new DatasourceQuery();
        $schema = SchemaController::read($this['schema']);

        $this['sorting']->appendQuery($query, $schema);
        $this['pagination']->appendQuery($query, $schema);

        $statement = Symphony::Database()->prepare($query);
        $valid = $statement->execute();

        // Build Entry Records
        if ($valid) {
            $statement->bindColumn('entry_id', $entryId, PDO::PARAM_INT);

            while ($row = $statement->fetch(PDO::FETCH_BOUND)) {
                yield Entry::loadFromId($entryId);
            }
        }
    }

    public function findSortingByField($field)
    {
        foreach ($this['sorting']->findAll() as $item) {
            if ($item->getGuid() !== $field->getGuid()) continue;
            if (get_class($item) !== get_class($field)) continue;

            return $item;
        }

        return false;
    }
}
