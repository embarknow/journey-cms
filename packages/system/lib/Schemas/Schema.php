<?php

namespace Embark\CMS\Schemas;

use Embark\CMS\Entries\Entry;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Metadata\ReferencedMetadataTrait;
use Embark\CMS\Metadata\Filters\Guid;
use Embark\CMS\SystemDateTime;
use DateTime;
use General;
use Symphony;

class Schema implements SchemaInterface
{
    use ReferencedMetadataTrait;

    public function __construct()
    {
        $this->setSchema([
            'fields' => [
                'required' =>    true,
                'type' =>        new FieldsList()
            ]
        ]);
    }

    public function countEntries()
    {
        try {
            $result = Symphony::Database()->query(
                "
                    SELECT
                        count(*) AS `count`
                    FROM
                        `entries` AS e
                    WHERE
                        e.schema_id = '%s'
                ",
                [$this->getGuid()]
            );

            if ($result->valid()) {
                return (integer)$result->current()->count;
            }
        }

        catch (Exception $error) {
            return 0;
        }

        return 0;
    }

    public function deleteEntries(array $entries)
    {
        Symphony::Database()->beginTransaction();

        try {
            foreach ($entries as $entryId) {
                $entry = Entry::loadFromId($entryId);

                // Delete all field data:
                foreach ($this->findAllFields() as $field) {
                    $field->deleteData($this, $entry, $field);
                }

                // Delete the entry record:
                $statement = Symphony::Database()->prepare("
                    delete from `entries` where
                        entry_id = :entryId
                ");

                $statement->execute([
                    ':entryId' =>   $entryId
                ]);
            }

            Symphony::Database()->commit();

            redirect($pageLink);
        }

        // Something went wrong, do not commit:
        catch (\Exception $error) {
            Symphony::Database()->rollBack();

            throw $error;
        }
    }

    public function findAllFields()
    {
        foreach ($this['fields']->findInstancesOf(FieldInterface::class) as $name => $value) {
            yield $name => $value;
        }
    }

    public function findField($handle)
    {
        foreach ($this['fields']->findAll() as $field) {
            if ($field['handle'] !== $handle) continue;

            return $field;
        }

        return false;
    }

    public function findFieldByGuid($guid)
    {
        foreach ($this['fields']->findAll() as $field) {
            if ($field->getGuid() !== $guid) continue;

            return $field;
        }

        return false;
    }

    public function withEntry($entryId = null)
    {
        if (isset($entryId)) {
            $statement = Symphony::Database()->prepare("
                select * from `entries` where
                    entry_id = :entryId
                limit 1
            ");

            $statement->execute([
                ':entryId' =>           $entryId
            ]);

            $data = $statement->fetch();
        }

        else {
            $date = new SystemDateTime();
            $data = (object)[
                'entry_id' =>           null,
                'schema_id' =>          $this->getGuid(),
                'user_id' =>            Symphony::User()->user_id,
                'creation_date' =>      $date->format(DateTime::W3C),
                'modification_date' =>  $date->format(DateTime::W3C)
            ];
        }

        $entry = new Entry($data);
        $entry->fromMetadata($this);

        return $entry;
    }
}
