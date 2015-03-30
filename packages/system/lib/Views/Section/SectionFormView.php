<?php

namespace Embark\CMS\Views\Section;

use Embark\CMS\Entries\EntryInterface;
use Embark\CMS\Structures\MetadataInterface;
use Embark\CMS\Structures\MetadataTrait;
use AdministrationPage;
use DOMElement;
use MessageStack;
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

        // Set page title and breadcrumb:
        if ($page instanceof AdministrationPage) {
            $page->Form->setAttribute('enctype', 'multipart/form-data');
            $page->setTitle(__('%1$s &ndash; %2$s', [__('Symphony'), $view['name']]));

            $page->appendBreadcrumb(Widget::Anchor($view['name'], $url));
            $page->appendBreadcrumb(Widget::Anchor(__('Create new'), $url . '/new'));
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

        // Validate fields:
        $headersAppended = [];

        foreach ($this->findAllFields() as $field) {
            $field['form']->appendHeaders($page, $field, $headersAppended);

            // Prepare the field data:
            $data = $field['data']->prepare($entry, $field, (
                isset($_POST['fields'][$field['schema']['handle']])
                    ? $_POST['fields'][$field['schema']['handle']]
                    : null
            ));

            // Validate the field data:
            if (isset($_POST['fields'])) {
                try {
                    $field['data']->validate($entry, $field, $data);
                }

                catch (\Exception $error) {
                    $field['form']->setError($field, $error);
                }
            }

            $field['form']->setData($field, $data);
        }
    }
}