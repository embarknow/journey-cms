<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Schemas\FieldsList;
use Embark\CMS\Structures\MetadataControllerInterface;
use Embark\CMS\Structures\MetadataControllerTrait;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Syncable\Controller as SyncController;
use Embark\CMS\Syncable\SyncableControllerInterface;
use Symphony;
use PDO;

class Controller implements MetadataControllerInterface, SyncableControllerInterface
{
    use MetadataControllerTrait {
        MetadataControllerTrait::delete as deleteFile;
    }

    const DIR = '/workspace/schemas';
    const FILE_EXTENSION = '.xml';

    protected $syncController;

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

    /**
     * Get statistics for a schema
     * @return object
     */
    protected static function syncStats(SyncController $syncController) {
        $fresh = $syncController->fresh();
        $stored = $syncController->stored();

        $newFields = $oldFields = [];
        $result = (object) [
            'schema' => (object) [
                'create' => false,
                'rename' => false,
                'stored' => $fresh->object,
                'fresh'  => $fresh->object
            ],
            'remove' => [],
            'rename' => [],
            'create' => [],
            'update' => []
        ];

        if (isset($stored->object)) {
            if ($fresh->object['resource']['handle'] !== $stored->object['resource']['handle']) {
                $result->schema->rename = true;
                $result->schema->stored = $stored->object;
            }

            if ($stored->object['fields'] instanceof FieldsList) {
                foreach ($stored->object['fields']->findAll() as $field) {
                    $oldFields[$field['schema']['guid']] = (object) [
                        'raw'   =>  iterator_to_array($field['schema']->findAll()),
                        'type'  =>  get_class($field),
                        'field' =>  $field
                    ];
                }
            }
        }

        // Compare to the new version:
        else {
            $stored = $fresh;
            $result->schema->create = true;
        }

        if ($fresh->object['fields'] instanceof FieldsList) {
            foreach ($fresh->object['fields']->findAll() as $field) {
                $newFields[$field['schema']['guid']] = (object) [
                    'raw'   =>  iterator_to_array($field['schema']->findAll()),
                    'type'  =>  get_class($field),
                    'field' =>  $field
                ];
            }
        }

        foreach ($newFields as $guid => $data) {
            // Field is being created:
            if (false === isset($oldFields[$guid])) {
                $result->create[$guid] = $data;
                continue;
            }

            // Field is being renamed:
            if (
                $result->schema->rename
                || $oldFields[$guid]->raw['handle'] !== $data->raw['handle']
            ) {
                if ($oldFields[$guid]->type === $data->type) {
                    $result->rename[$guid] = (object) [
                        'handle' => $data->raw['handle'],
                        'old'    => $oldFields[$guid],
                        'new'    => $data
                    ];
                }

                // Type has changed:
                else {
                    $result->remove[$guid] = $oldFields[$guid];
                    $result->create[$guid] = $data;
                    continue;
                }
            }

            // Field definition has changed:
            if ($oldFields[$guid]->raw != $data->raw) {
                if ($oldFields[$guid]->type === $data->type) {
                    $result->update[$guid] = (object) [
                        'handle' => $data->raw['handle'],
                        'old'    => $oldFields[$guid],
                        'new'    => $data
                    ];
                }

                // Type has changed:
                else {
                    $result->remove[$guid] = $oldFields[$guid];
                    $result->create[$guid] = $data;
                    continue;
                }
            }
        }

        foreach ($oldFields as $guid => $data) {
            if (isset($newFields[$guid])) {
                continue;
            }

            $result->remove[$guid] = $data;
        }

        $result->synced = (
            $result->synced
            && empty($result->remove)
            && empty($result->rename)
            && empty($result->create)
            && empty($result->update)
        );

        var_dump($result);die;
        return $result;
    }

    /**
     * Synchronize Schema data
     * @param  Schema $schema
     * @return void
     */
    public static function sync(MetadataInterface $object)
    {
        $schema = $object;

        $syncController = new SyncController(Symphony::Database());
        $syncController->setObject($schema);

        if (!$syncController->status()) {
            $stats = static::syncStats($syncController);
            $stored = $syncController->stored();

            // Remove fields:
            foreach ($stats->remove as $guid => $data) {
                $data->field['schema']->delete($schema, $data->field);
            }

            // Rename fields:
            foreach ($stats->rename as $guid => $data) {
                $data->new->field['schema']->rename($schema, $data->new->field, $stats->schema->stored, $data->old->field);
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
                    ':new' => $stats->schema->fresh['resource']['handle'],
                    ':old' => $stats->schema->stored['resource']['handle']
                ]);
            }

            $syncController->sync();
        }
    }
}
