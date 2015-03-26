<?php

namespace Embark\CMS\Fields\Upload;

use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Entry;
use Symphony;

class Field implements MetadataInterface
{
    use MetadataTrait;

    public function getParameterOutputValue($data, Entry $entry = null)
    {
        if (isset($data->file)) {
            return rtrim($data->path, '/') . '/' . $data->file;
        }
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
