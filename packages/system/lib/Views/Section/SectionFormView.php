<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Fields\FieldInterface;
use Embark\CMS\Fields\FieldFormInterface;
use Embark\CMS\Fields\FieldPreviewInterface;
use Embark\CMS\Schemas\SchemaInterface;
use Embark\CMS\Metadata\MetadataInterface;
use Embark\CMS\Metadata\MetadataReferenceInterface;
use Embark\CMS\Metadata\MetadataTrait;
use Embark\CMS\SystemDateTime;
use AdministrationPage;
use AlertStack;
use DOMElement;
use Entry;
use General;
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

    public function appendHeaderTo(HTMLDocument $page, SectionView $view, EntryInterface $entry)
    {
        $url = ADMIN_URL . '/publish/' . $view['resource']['handle'];
        $title = __('Create new');
        $link = $url . '/new';

        if (isset($entry->entry_id)) {
            $title = __('Edit entry');

            if (isset($view['title']['field'])) {
                $preview = $view['title']->resolveInstanceOf(FieldPreviewInterface::class);
                $title = $preview->getTitle($entry);
                $link = $url . '/edit/' . $entry->entry_id;
            }
        }

        $page->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', [
            __('Symphony'), $view['name'], $title
        ]));
        $page->appendBreadcrumb(Widget::Anchor($view['name'], $url));
        $page->appendBreadcrumb(Widget::Anchor($title, $link));
        $page->Form->setAttribute('enctype', 'multipart/form-data');

        if (isset($_GET['saved'])) {
            $this->appendAlert($page, $url, 'Entry updated at %s. <a href="%s">Create another?</a> <a href="%s">View all Entries</a>');
        }

        else if (isset($_GET['created'])) {
            $this->appendAlert($page, $url, 'Entry updated at %s. <a href="%s">Create another?</a> <a href="%s">View all Entries</a>');
        }
    }

    public function appendFooterTo(HTMLDocument $page, SectionView $view, EntryInterface $entry)
    {
        $editing = isset($entry->entry_id);

        $actions = $page->createElement('div');
        $actions->setAttribute('class', 'actions');
        $page->Form->appendChild($actions);

        $actions->appendChild(Widget::Submit(
            'action[save]',
            __('Create Entry'),
            [
                'accesskey' => 's'
            ]
        ));

        if ($editing) {
            $actions->appendChild(
                Widget::Submit(
                    'action[delete]', __('Delete'),
                    [
                        'class' => 'confirm delete',
                        'title' => __('Delete this entry'),
                    ]
                )
            );
        }
    }

    public function appendAlert(HTMLDocument $page, $url, $message)
    {
        $page->alerts()->append(
            __($message, [
                General::getTimeAgo(__SYM_TIME_FORMAT__),
                $url . '/new',
                $url
            ]),
            AlertStack::SUCCESS
        );
    }

    public function appendFormTo(HTMLDocument $page, SectionView $view, EntryInterface $entry)
    {
        $url = ADMIN_URL . '/publish/' . $view['resource']['handle'];
        $saving = isset($_POST['fields']);
        $deleting = isset($_POST['action']['delete']);
        $editing = isset($entry->entry_id);
        $headersAppended = [];
        $finally = [];
        $success = true;

        if ($deleting) {
            Symphony::Database()->beginTransaction();

            try {
                // Delete all field data:
                foreach ($entry->findAllFields() as $field) {
                    $field['data']->delete($entry, $field);
                }

                // Delete the entry record:
                $statement = Symphony::Database()->prepare("
                    delete from `entries` where
                        entry_id = :entryId
                ");

                $statement->execute([
                    ':entryId' =>   $entry->entry_id
                ]);

                Symphony::Database()->commit();

                redirect($url);
            }

            // Something went wrong, do not commit:
            catch (\Exception $error) {
                Symphony::Database()->rollBack();

                $page->alerts()->append(
                    __('An error occurred while deleting this entry. <a class="more">Show the error.</a>'),
                    AlertStack::ERROR,
                    $error
                );

                // Todo: Log this exception.
            }
        }

        // Build basic form layout:
        foreach ($this->findInstancesOf(SectionFormRow::class) as $item) {
            $item->appendRow($page->Form);
        }

        if ($saving) {
            // Begin a transaction to commit all of the field changes:
            Symphony::Database()->beginTransaction();

            // Prepare a new entry:
            if (false === $editing) {
                $entry->entry_id = Entry::generateID($entry->schema_id, $entry->user_id);
            }
        }

        // Validate fields:
        foreach ($this->findAllFields() as $processor) {
            $field = $processor['field']->resolveInstanceOf(FieldInterface::class);
            $form = $processor['form']->resolveInstanceOf(FieldFormInterface::class);

            // Add headers to the page:
            $form->appendPublishHeaders($page, $entry, $field, $headersAppended);

            // Load the data:
            $data = $processor->read($entry, $field, $form);
            $form->setData($entry, $field, $data);

            // Validate the field data:
            if ($saving) {
                try {
                    $processor->validate($entry, $field, $form, $data);
                    $processor->write($entry, $field, $form, $data);

                    $finally[] = function() use ($processor, $entry, $field, $form, $data) {
                        $processor->finalize($entry, $field, $form, $data);
                    };
                }

                catch (\Exception $error) {
                    $form->setError($entry, $field, $error);
                    $success = false;
                }
            }
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

                // Finalize fields:
                foreach ($finally as $final) {
                    $final();
                }

                // Go to the success page:
                redirect($url . '/edit/' . $entry->entry_id . (
                    $editing
                        ? '?saved'
                        : '?created'
                ));
            }

            else {
                // Cancel the transaction and roll back the changes:
                Symphony::Database()->rollBack();
            }
        }
    }
}
