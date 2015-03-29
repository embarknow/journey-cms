<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Schemas\FieldsList;
use Embark\CMS\Structures\MetadataControllerInterface;
use Embark\CMS\Structures\MetadataControllerTrait;
use Embark\CMS\Structures\MetadataInterface;
use Symphony;
use PDO;

class Controller implements MetadataControllerInterface
{
    use MetadataControllerTrait {
        MetadataControllerTrait::delete as deleteFile;
    }

    const DIR = '/workspace/schemas';
    const FILE_EXTENSION = '.xml';

    public static function delete(MetadataInterface $object)
    {
        if (static::deleteFile($object)) {
            // Delete field data:
            if ($object['fields'] instanceof FieldsList) {
                foreach ($object['fields']->findAll() as $field) {
                    $field['schema']->delete($object, $field);
                }
            }

            // Delete entries:
            $statement = Symphony::Database()->prepare('
                delete from `entries` where
                    `schema` = :handle
            ');
            $statement->execute([
                ':handle' => $object['resource']['handle']
            ]);

            // Delete sync information:
            $statement = Symphony::Database()->prepare('
                delete from `sync` where
                    `guid` = :guid
            ');
            $statement->execute([
                ':guid' => $object['guid']
            ]);

            return true;
        }

        return false;
    }

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
                `sync` AS s
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
                foreach ($oldSchema['fields']->findAll() as $field) {
                    $old[$field['schema']['guid']] = (object)[
                        'raw' =>    iterator_to_array($field['schema']->findAll()),
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
            foreach ($newSchema['fields']->findAll() as $field) {
                $new[$field['schema']['guid']] = (object)[
                    'raw' =>    iterator_to_array($field['schema']->findAll()),
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
            $data->field['schema']->delete($schema, $data->field);
        }

        // Rename fields:
        foreach ($stats->rename as $guid => $data) {
            $data->new->field['schema']->rename($schema, $data->new->field, $stats->schema->old, $data->old->field);
        }

        // Create fields:
        foreach ($stats->create as $guid => $data) {
            $data->field['schema']->create($schema, $data->field);
        }

        // Move entries to the new schema:
        if ($stats->schema->rename) {
            $statement = Symphony::Database()->prepare('
                update `entries` set
                    `schema` = :new
                where
                    `schema` = :old
            ');
            $statement->execute([
                ':new' => $stats->schema->new['resource']['handle'],
                ':old' => $stats->schema->old['resource']['handle']
            ]);
        }

        // Remove old sync data:
        $statement = Symphony::Database()->prepare('
            delete from `sync` where
                `guid` = :guid
        ');
        $statement->execute([
            ':guid' => $schema['guid']
        ]);

        // Create new sync data:
        $statement = Symphony::Database()->prepare('
            insert into `sync` set
                `handle` = :handle,
                `guid` = :guid,
                `object` = :object
        ');
        $statement->execute([
            ':handle' => $schema['resource']['handle'],
            ':guid' =>   $schema['guid'],
            ':object' => serialize($schema)
        ]);
    }
}
