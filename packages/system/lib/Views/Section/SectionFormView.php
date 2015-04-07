<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Schemas\Controller as SchemaController;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use Embark\CMS\SystemDateTime;
use AdministrationPage;
use DOMElement;
use Entry;
use HTMLDocument;
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

    protected function appendHeader(HTMLDocument $page, SectionView $view, SchemaInterface $schema, EntryInterface $entry)
    {
        $url = ADMIN_URL . '/publish/' . $view['resource']['handle'];
        $title = __('Create new');
        $link = $url . '/new';

        if (isset($entry->entry_id)) {
            $title = __('Edit entry');

            if (isset($view['form']['title']) && $view['form']['title'] instanceof FieldInterface) {
                $field = $schema->findFieldByGuid($view['form']['title']['schema']['guid']);

                if ($field instanceof FieldInterface) {
                    $data = $field['data']->read($schema, $entry, $field);

                    if (isset($data->value)) {
                        $title = $data->value;
                        $url . '/edit/' . $entry->entry_id;
                    }
                }
            }
        }

        $page->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', [
            __('Symphony'), $view['name'], $title
        ]));
        $page->appendBreadcrumb(Widget::Anchor($view['name'], $url));
        $page->appendBreadcrumb(Widget::Anchor($title, $link));
        $page->Form->setAttribute('enctype', 'multipart/form-data');
    }

    public function appendFooter(HTMLDocument $page, SectionView $view, SchemaInterface $schema, EntryInterface $entry)
    {
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
    }

    public function appendForm(HTMLDocument $page, SectionView $view, EntryInterface $entry)
    {
        $url = ADMIN_URL . '/publish/' . $view['resource']['handle'];
        $schema = SchemaController::read($view['schema']);
        $saving = isset($_POST['fields']);
        $editing = isset($entry->entry_id);
        $headersAppended = [];
        $success = true;

        // Set page title and breadcrumb:
        $this->appendHeader($page, $view, $schema, $entry);

        // Build basic form layout:
        foreach ($this->findAll() as $item) {
            if ($item instanceof SectionFormRow) {
                $item->appendRow($page->Form);
            }
        }

        $this->appendFooter($page, $view, $schema, $entry);

        if ($saving) {
            // Begin a transaction to commit all of the field changes:
            Symphony::Database()->beginTransaction();

            // Prepare a new entry:
            if (false === $editing) {
                $entry->schema = $schema['resource']['handle'];
                $entry->user = Symphony::User()->user_id;
                $entry->entry_id = Entry::generateID($entry->schema, $entry->user);
            }
        }

        // Validate fields:
        foreach ($this->findAllFields() as $field) {
            $handle = $field['schema']['handle'];

            // Add headers to the page:
            $field['form']->appendPublishHeaders($page, $entry, $field, $headersAppended);

            // Load the data:
            $data = $field['data']->read($schema, $entry, $field);

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
                        entry_id = :entryId
                ");

                $statement->execute([
                    ':entryId' =>   $entry->entry_id,
                    ':date' =>      $date->format(SystemDateTime::W3C)
                ]);

                // Keep these changes:
                Symphony::Database()->commit();

                // Go to the success page:
                redirect($url . '/edit/' . $entry->entry_id . (
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