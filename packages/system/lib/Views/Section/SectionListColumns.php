<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldColumnInterface;
use Embark\CMS\Fields\FieldPreviewInterface;
use Embark\CMS\Fields\FieldPreviewCaptionInterface;
use Embark\CMS\Fields\FieldPreviewFigureInterface;
use Embark\CMS\Link;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Schemas\SchemaSelectQuery;
use AlertStack;
use DOMElement;
use Entry;
use HTMLDocument;
use PDO;
use Symphony;
use Widget;

class SectionListColumns implements MetadataInterface
{
    use MetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'column' => [
                'list' =>   true
            ]
        ]);
    }

    public function findAllColumns()
    {
        return $this->findInstancesOf(FieldColumnInterface::class);
    }

    public function findFirstColumn()
    {
        foreach ($this->findAllColumns() as $item) {
            return $item;
        }

        return false;
    }

    public function findColumnByName($name)
    {
        foreach ($this->findAllColumns() as $item) {
            if ($item['name'] !== $name) continue;

            return $item;
        }

        return false;
    }
}
