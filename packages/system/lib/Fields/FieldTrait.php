<?php

namespace Embark\CMS\Fields;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\ReferencedMetadataTrait;
use Embark\CMS\Schemas\SchemaInterface;
use MessageStack;
use PDO;
use Symphony;
use SymphonyDOMElement;
use Widget;

/**
 * Trait implementing ReferencedMetadataInterface
 *
 * @see ReferencedMetadataInterface
 */
trait FieldTrait
{
    use ReferencedMetadataTrait;

    public function appendSchemaHandleSettings(SymphonyDOMElement $wrapper, MessageStack $errors)
    {
        $document = $wrapper->ownerDocument;
        $label = Widget::Label(__('Handle'));
        $label->setAttribute('class', 'field-handle');
        $label->appendChild(Widget::Input('handle', $this['handle']));

        if ($errors->{'handle'}) {
            $label = Widget::wrapFormElementWithError($label, $errors->{'handle'});
        }

        $wrapper->appendChild($label);
    }

    public function renameSchema(SchemaInterface $newSchema, FieldInterface $newField, SchemaInterface $oldSchema, FieldInterface $oldField)
    {
        // TODO: Throw an exception if $*Field['schema'] is unset.
        $oldTable = Symphony::Database()->createDataTableName(
            $oldSchema['resource']['handle'],
            $oldField['handle'],
            $oldField->getGuid()
        );
        $newTable = Symphony::Database()->createDataTableName(
            $newSchema['resource']['handle'],
            $newField['handle'],
            $newField->getGuid()
        );
        $statement = Symphony::Database()->prepare("
            alter table `{$oldTable}`
            rename to `{$newTable}
        ");

        return $statement->execute();
    }

    public function deleteSchema(SchemaInterface $schema)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $schema['resource']['handle'],
            $this['handle'],
            $this->getGuid()
        );
        $statement = Symphony::Database()->prepare("
            drop table if exists `{$table}`
        ");

        return $statement->execute();
    }

    public function deleteData(EntryInterface $entry, MetadataInterface $settings)
    {
        // TODO: Throw an exception if $field['schema'] is unset.
        $table = Symphony::Database()->createDataTableName(
            $entry['resource']['handle'],
            $this['handle'],
            $this->getGuid()
        );

        $statement = Symphony::Database()->prepare("
            delete from `$table` where
                `entry_id` = :entryId
        ");
        $statement->bindValue(':entryId', $entry->entry_id, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function readData(EntryInterface $entry, MetadataInterface $settings)
    {
        $table = Symphony::Database()->createDataTableName(
            $entry['resource']['handle'],
            $this['handle'],
            $this->getGuid()
        );

        $statement = Symphony::Database()->prepare("
            select * from `$table` where
                `entry_id` = :entryId
        ");
        $statement->bindValue(':entryId', $entry->entry_id, PDO::PARAM_INT);

        if ($statement->execute()) {
            return $this->prepareData($entry, $settings, $statement->fetch());
        }

        return $field->prepareData($entry, $settings, null);
    }
}
