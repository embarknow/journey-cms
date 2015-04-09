<?php

namespace Embark\CMS\Syncable;

use StdClass;
use PDO;
use Embark\CMS\Database\Connection;
use Embark\CMS\Structures\MetadataInterface;

class Controller
{
    /**
     * Data for the new version of the object to synchronisze
     * @var array
     */
    protected $fresh;

    /**
     * Data for the stored version of the object to synchronize
     * @var array
     */
    protected $stored;

    /**
     * Backup of a performed synchronization
     * @var array
     */
    protected $backup;

    /**
     * Construct an instance by providing an instance of a metadata object
     * @param Connection $database
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
        $this->fresh = new StdClass;
        $this->stored = new StdClass;
    }

    /**
     * Set the object to be synchronized
     * @param MetadataInterface $object
     */
    public function setObject(MetadataInterface $object)
    {
        $this->fresh->hash = sha1(serialize($object));
        $this->fresh->object = $object;
        $this->fetchStoredObject($object['guid']);
    }

    /**
     * Return whether the objects are in sync
     * @return boolean
     */
    public function status()
    {
        if (!isset($this->stored->object)) {
            return false;
        }

        return (
            $this->stored->hash === $this->fresh->hash
            && $this->stored->object == $this->fresh->object
        );
    }

    /**
     * Get the stored object data
     * @return array
     */
    public function stored()
    {
        return $this->stored;
    }

    /**
     * Get the fresh object data
     * @return array
     */
    public function fresh()
    {
        return $this->fresh;
    }

    /**
     * Synchronise the objects
     * @return void
     */
    public function sync()
    {
        // Remove old sync data:
        $statement = $this->database->prepare('
            delete from `sync`
            where `object_id` = :guid
        ');
        $statement->execute([
            ':guid' => $this->stored->object['guid']
        ]);

        // Create the new sync data:
        $statement = $this->database->prepare('
            insert into `sync` set
                `object_id` = :guid,
                `object` = :object
        ');
        $statement->execute([
            ':guid'   => $this->fresh->object['guid'],
            ':object' => serialize($this->fresh->object)
        ]);

        // Swap the instance data around
        $this->backup = $this->stored;
        $this->stored = $this->fresh;
    }

    /**
     * Revert a previous synchronization
     * @return void
     */
    public function revert()
    {
        // Remove new sync data:
        $statement = $this->database->prepare('
            delete from `sync`
            where `object_id` = :guid
        ');
        $statement->execute([
            ':guid' => $this->stored->object['guid']
        ]);

        // Recreate the old sync data:
        $statement = $this->database->prepare('
            insert into `sync` set
                `object_id` = :guid,
                `object` = :object
        ');
        $statement->execute([
            ':guid'   => $this->backup->object['guid'],
            ':object' => serialize($this->backup->object)
        ]);

        $this->stored = $this->backup;
        $this->backup = null;
    }

    /**
     * Get the stored object from the database
     * @param string $guid
     * @return void
     */
    protected function fetchStoredObject($guid)
    {
        $object = null;

        $statement = $this->database->prepare('
            select `s`.`object`
            from `sync` as `s`
            where `s`.`object_id` = :guid
        ');

        $statement->bindParam(':guid', $guid, PDO::PARAM_STR);

        if ($statement->execute()) {
            $object = $statement->fetch()->object;

            $statement->closeCursor();
        }

        // If there is an object result, then store it
        if ($object) {
            $this->stored->hash = sha1($object);
            $this->stored->object = unserialize($object);
        }
    }
}
