<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Schemas\Controller as SchemaController;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use AdministrationPage;
use DOMElement;
use Entry;
use MessageStack;
use Symphony;
use Widget;

class SectionFormView implements MetadataInterface
{
    use MetadataTrait;

    public function __construct(SectionView $view)
    {
        $this->setSchema([
            'row' => [
                'list' =>   true,
                'type' =>   new SectionFormRow($view)
            ]
        ]);
    }

    public function findAllFields()
    {
        foreach ($this->findAll() as $item) {
            if ($item instanceof SectionFormRow) {
                foreach ($item->findAllFields() as $field) {
                    yield $field;
                }
            }
        }
    }

    public function appendForm($page, SectionView $view, EntryInterface $entry)
    {
        $url = ADMIN_URL . '/publish/' . $view['resource']['handle'];
        $schema = SchemaController::read($view['schema']);
        $saving = isset($_POST['fields']);
        $editing = isset($entry->id);
        $headersAppended = [];
        $success = true;

        // Set page title and breadcrumb:
        if ($page instanceof AdministrationPage) {
            $page->Form->setAttribute('enctype', 'multipart/form-data');
            $page->setTitle(__('%1$s &ndash; %2$s', [__('Symphony'), $view['name']]));

            $page->appendBreadcrumb(Widget::Anchor($view['name'], $url));

            if ($editing) {
                $page->appendBreadcrumb(Widget::Anchor(__('Edit entry'), $url . '/edit/' . $entry->id));
            }

            else {
                $page->appendBreadcrumb(Widget::Anchor(__('Create new'), $url . '/new'));
            }
        }

        // Build basic form layout:
        foreach ($this->findAll() as $item) {
            if ($item instanceof SectionFormRow) {
                $item->appendRow($page->Form);
            }
        }

        $div = $page->createElement('div');
        $div->setAttribute('class', 'actions');
        $div->appendChild(Widget::Submit(
            'action[save]',
            __('Create Entry'),
            [
                'accesskey' => 's'
            ]
        ));
        $page->Form->appendChild($div);

        if ($saving) {
            // Begin a transaction to commit all of the field changes:
            Symphony::Database()->beginTransaction();

            // Prepare a new entry:
            if (false === $editing) {
                $entry->schema = $schema['resource']['handle'];
                $entry->user = Symphony::User()->id;
                $entry->id = Entry::generateID($entry->schema, $entry->user);
            }
        }

        // Validate fields:
        foreach ($this->findAllFields() as $field) {
            $handle = $field['schema']['handle'];

            // Add headers to the page:
            $field['form']->appendHeaders($page, $entry, $field, $headersAppended);

            // Load the data:
            $data = $field['data']->read($schema, $entry, $field);

            // var_dump($data);

            // Validate the field data:
            if ($saving) {
                $post = (
                    isset($_POST['fields'][$handle])
                        ? $_POST['fields'][$handle]
                        : null
                );
                $data = $field['data']->prepare($schema, $entry, $field, $post, $data);

                try {
                    $field['data']->validate($schema, $entry, $field, $data);
                    $field['data']->write($schema, $entry, $field, $data);
                }

                catch (\Exception $error) {
                    $field['form']->setError($entry, $field, $error);
                    $success = false;
                }
            }

            $field['form']->setData($entry, $field, $data);
        }

        if ($saving) {
            // All of the  fields were valid:
            if ($success) {
                $date = new SystemDateTime();

                // Update the entry modification date:
                $statement = Symphony::Database()->prepare("
                    update `entries` set
                        modification_date = :date
                    where
                        id = :entryId
                ");

                $statement->execute([
                    ':entryId' =>   $entry->id,
                    ':date' =>      $date->format(SystemDateTime::W3C)
                ]);

                // Keep these changes:
                Symphony::Database()->commit();

                // Go to the success page:
                redirect($url . '/edit/' . $entry->id . (
                    $editing
                        ? '/:saved'
                        : '/:created'
                ));
            }

            else {
                // Cancel the transaction and roll back the changes:
                Symphony::Database()->rollBack();
            }
        }
    }
}