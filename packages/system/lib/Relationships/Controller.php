<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Schemas\FieldsList;
use Embark\CMS\Metadata\MetadataControllerInterface;
use Embark\CMS\Metadata\MetadataControllerTrait;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Syncable\Controller as SyncController;
use Embark\CMS\Syncable\SyncableControllerInterface;
use Symphony;
use PDO;

class Controller implements MetadataControllerInterface, SyncableControllerInterface
{
    use MetadataControllerTrait {
        MetadataControllerTrait::delete as deleteFile;
    }

    const DIR = '/blueprints/relationships';
    const FILE_EXTENSION = '.xml';

    protected $syncController;

    public static function delete(MetadataInterface $object)
    {
        if (static::deleteFile($object)) {
            // Delete field data:
            if ($object['fields'] instanceof FieldsList) {
                foreach ($object['fields']->findAll() as $field) {
                    $field->deleteSchema($object);
                }
            }

            // Delete entries:
            $statement = Symphony::Database()->prepare('
                delete from `relationships` where
                    `relationship_id` = :guid
            ');
            $statement->execute([
                ':guid' => $object->getGuid()
            ]);

            // Delete sync information:
            $statement = Symphony::Database()->prepare('
                delete from `sync` where
                    `object_id` = :guid
            ');
            $statement->execute([
                ':guid' => $object->getGuid()
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get statistics for a relationship
     * @return object
     */
    protected static function syncStats(SyncController $syncController) {
        $fresh = $syncController->fresh();
        $stored = $syncController->stored();

        $newFields = $oldFields = [];
        $result = (object) [
            'relationship' => (object) [
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
                $result->relationship->rename = true;
                $result->relationship->stored = $stored->object;
            }

            if ($stored->object['fields'] instanceof FieldsList) {
                foreach ($stored->object['fields']->findAll() as $field) {
                    $oldFields[$field->getGuid()] = (object) [
                        'raw'   =>  iterator_to_array($field->findAll()),
                        'type'  =>  get_class($field),
                        'field' =>  $field
                    ];
                }
            }
        }

        // Compare to the new version:
        else {
            $stored = $fresh;
            $result->relationship->create = true;
        }

        if ($fresh->object['fields'] instanceof FieldsList) {
            foreach ($fresh->object['fields']->findAll() as $field) {
                $newFields[$field->getGuid()] = (object) [
                    'raw'   =>  iterator_to_array($field->findAll()),
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
                $result->relationship->rename
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

        return $result;
    }

    /**
     * Synchronize Schema data
     * @param  Schema $relationship
     * @return void
     */
    public static function sync(MetadataInterface $object)
    {
        $relationship = $object;

        $syncController = new SyncController(Symphony::Database());
        $syncController->setObject($relationship);

        if (!$syncController->status()) {
            $stats = static::syncStats($syncController);
            $stored = $syncController->stored();

            // Remove fields:
            foreach ($stats->remove as $guid => $data) {
                $data->field->deleteSchema($relationship);
            }

            // Rename fields:
            foreach ($stats->rename as $guid => $data) {
                $data->new->field->renameSchema($relationship, $data->new->field, $stats->relationship->stored, $data->old->field);
            }

            // Create fields:
            foreach ($stats->create as $guid => $data) {
                $data->field->createSchema($relationship, $data->field);
            }

            $syncController->sync();
        }
    }
}
