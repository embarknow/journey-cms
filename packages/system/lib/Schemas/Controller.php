<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Schemas\FieldsList;
use Embark\CMS\Structures\MetadataControllerTrait;
use Embark\CMS\Structures\MetadataInterface;
use Symphony;
use PDO;

class Controller
{
    use MetadataControllerTrait {
        // MetadataTrait::
    }

    const DIR = WORKSPACE . '/schemas';
    const FILE_EXTENSION = '.xml';

    public static function syncStats(Schema $newSchema) {
        $new = $old = [];
        $result = (object)[
            'synced'    => true,
            'schema'   => (object)[
                'create'    => false,
                'rename'    => false,
                'old'       => $newSchema,
                'new'       => $newSchema
            ],
            'remove'    => [],
            'rename'    => [],
            'create'    => [],
            'update'    => []
        ];

        // Fetch the currently active schema:
        $statement = Symphony::Database()->prepare('
            SELECT
                s.object
            FROM
                `schemas` AS s
            WHERE
                s.guid = ?
        ');
        $statement->bindValue(1, $newSchema['guid'], PDO::PARAM_STR);

        // We found it:
        if ($statement->execute()) {
            $oldSchema = unserialize($statement->fetch()->object);
            $statement->closeCursor();
        }

        if ($oldSchema) {
            if ($oldSchema['resource']['handle'] !== $newSchema['resource']['handle']) {
                $result->synced = false;
                $result->schema->rename = true;
                $result->schema->old = $oldSchema;
            }

            if ($oldSchema['fields'] instanceof FieldsList) {
                foreach ($oldSchema['fields']->findAllWithGuids() as $guid => $field) {
                    $old[$guid] = (object)[
                        'raw' =>   iterator_to_array($field->findAll()),
                        'type' =>   get_class($field),
                        'field' =>  $field
                    ];
                }
            }
        }

        // Compare to the new version:
        else {
            $oldSchema = $newSchema;
            $result->synced = false;
            $result->schema->create = true;
        }

        if ($newSchema['fields'] instanceof FieldsList) {
            foreach ($newSchema['fields']->findAllWithGuids() as $guid => $field) {
                $new[$guid] = (object)[
                    'raw' =>   iterator_to_array($field->findAll()),
                    'type' =>   get_class($field),
                    'field' =>  $field
                ];
            }
        }

        foreach ($new as $guid => $data) {
            // Field is being created:
            if (false === isset($old[$guid])) {
                $result->create[$guid] = $data;
                continue;
            }

            // Field is being renamed:
            if (
                $result->schema->rename
                || $old[$guid]->raw['handle'] !== $data->raw['handle']
            ) {
                if ($old[$guid]->type === $data->type) {
                    $result->rename[$guid] = (object)[
                        'handle'    => $data->raw['handle'],
                        'old'       => $old[$guid],
                        'new'       => $data
                    ];
                }

                // Type has changed:
                else {
                    $result->remove[$guid] = $old[$guid];
                    $result->create[$guid] = $data;
                    continue;
                }
            }

            // Field definition has changed:
            if ($old[$guid]->raw != $data->raw) {
                if ($old[$guid]->type === $data->type) {
                    $result->update[$guid] = (object)[
                        'handle' => $data->raw['handle'],
                        'old' =>    $old[$guid],
                        'new' =>    $data
                    ];
                }

                // Type has changed:
                else {
                    $result->remove[$guid] = $old[$guid];
                    $result->create[$guid] = $data;
                    continue;
                }
            }
        }

        foreach ($old as $guid => $data) {
            if (isset($new[$guid])) continue;

            $result->remove[$guid] = $data;
        }

        $result->synced = (
            $result->synced
            && empty($result->remove)
            && empty($result->rename)
            && empty($result->create)
            && empty($result->update)
        );

        return $result;
    }

    public static function sync(Schema $schema)
    {
        $stats = static::syncStats($schema);

        // Remove fields:
        foreach ($stats->remove as $guid => $data) {
            $data->field->removeTable($schema);
        }

        // Rename fields:
        foreach ($stats->rename as $guid => $data) {
            $data->new->field->renameTable($schema, $stats->schema->old, $data->old->field);
        }

        // Create fields:
        foreach ($stats->create as $guid => $data) {
            $data->field->createTable($schema);
        }

        // Remove old sync data:
        $statement = Symphony::Database()->prepare('
            delete from `schemas` where
                guid = :guid
        ');
        $statement->execute([
            ':guid' =>          $schema['guid']
        ]);

        // Create new sync data:
        $statement = Symphony::Database()->prepare('
            insert into `schemas` set
                guid = :guid,
                object = :object
        ');
        $statement->execute([
            ':guid' =>          $schema['guid'],
            ':object' =>        serialize($schema)
        ]);
    }
}