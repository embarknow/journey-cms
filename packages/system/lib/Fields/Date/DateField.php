<?php

namespace Embark\CMS\Fields\Date;

use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\SystemDateTime;
use Entry;
use Symphony;

class Field implements MetadataInterface
{
    use MetadataTrait;

    public function getParameterOutputValue($data, Entry $entry = null)
    {
        if (is_null($data->value)) return;

        $date = new SystemDateTime($data->value);

         return $date->format('Y-m-d H:i:s');
    }

    public function loadDataFromDatabaseEntries($section, $entryIds)
    {
        try {
            $result = [];
            $rows = Symphony::Database()->query(
                "SELECT * FROM `data_%s_%s` WHERE `entry_id` IN (%s) ORDER BY `id` ASC",
                [
                    $section,
                    $this['name'],
                    implode(',', $entryIds)
                ]
            );

            foreach ($rows as $row) {
                $result[] = $row;
            }

            return $result;
        }

        catch (DatabaseException $e) {
            return [];
        }
    }
}
