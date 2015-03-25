<?php

namespace Embark\CMS\Actors\Section;

use Embark\CMS\Structures\Pagination;
use Embark\CMS\Structures\QueryOptions;
use Embark\CMS\Structures\Sorting;
use Administration;
use Context;
use Duplicator;
use General;
use Layout;
use MessageStack;
use Section;
use SectionIterator;
use SymphonyDOMElement;
use Widget;

class DatasourceForm
{
	protected $datasource;

	public function __construct(Datasource $datasource)
	{
		$this->datasource = $datasource;
	}

	public function view(SymphonyDOMElement $wrapper, MessageStack $errors) {
		$page = Administration::instance()->Page;
		$page->insertNodeIntoHead($page->createScriptElement(URL . '/extensions/ds_sections/assets/view.js'));

		$layout = new Layout();
		$left = $layout->createColumn(Layout::SMALL);
		$right = $layout->createColumn(Layout::SMALL);

	//	Essentials --------------------------------------------------------

		$fieldset = Widget::Fieldset(__('Essentials'));

		// Name:
		$label = Widget::Label(__('Name'));
		$input = Widget::Input('fields[name]', General::sanitize($this->datasource['name']));
		$label->appendChild($input);

		if (isset($errors->{'name'})) {
			$label = Widget::wrapFormElementWithError($label, $errors->{'name'});
		}

		$fieldset->appendChild($label);

		// Section:
		$field_groups = $options = array();

		foreach (new SectionIterator as $section) {
			$field_groups[$section->handle] = array(
				'fields' =>		$section->fields,
				'section' =>	$section
			);

			$options[] = array($section->handle, ($this->datasource['section'] == $section->handle), $section->name);
		}

		$label = Widget::Label(__('Section'));
		$label->appendChild(Widget::Select('fields[section]', $options, array('id' => 'context')));

		$fieldset->appendChild($label);
		$left->appendChild($fieldset);

	//	Filtering ---------------------------------------------------------

		$fieldset = Widget::Fieldset(__('Filtering'), '<code>{$param}</code> or <code>Value</code>');

		$container_filter_results = $page->createElement('div');
		$fieldset->appendChild($container_filter_results);

		$left->appendChild($fieldset);

	//	Sorting -----------------------------------------------------------

		$fieldset = Widget::Fieldset(__('Sorting'));

		$container_sort_by = $page->createElement('div');
		$fieldset->appendChild($container_sort_by);

		$label = Widget::Label(__('Sort Order'));

		$options = [
			['asc', ('asc' == $this->datasource['sorting']['direction']), __('Ascending')],
			['desc', ('desc' == $this->datasource['sorting']['direction']), __('Descending')],
			['random', ('random' == $this->datasource['sorting']['direction']), __('Random')]
		];

		$label->appendChild(Widget::Select('fields[sorting][direction]', $options));
		$fieldset->appendChild($label);

		$fieldset->appendChild(Widget::Input('fields[sorting][append]', 'no', 'hidden'));

		$label = Widget::Label(__('Append sorting data'));
		$input = Widget::Input('fields[sorting][append]', 'yes', 'checkbox');

		if ($this->datasource['sorting']['append']) {
			$input->setAttribute('checked', 'checked');
		}

		$label->prependChild($input);
		$fieldset->appendChild($label);
		$right->appendChild($fieldset);

	//	Pagination --------------------------------------------------------

		$fieldset = Widget::Fieldset(__('Limiting'), '<code>{$param}</code> or <code>Value</code>');

		// Show a maximum of # results
		$label = Widget::Label(__('Limit results per page'));
		$input = Widget::Input('fields[pagination][limit]', $this->datasource['pagination']['limit']);

		$label->appendChild($input);

		if (isset($errors->{'pagination.limit'})) {
			$label = Widget::wrapFormElementWithError($label, $errors->{'pagination.limit'});
		}

		$fieldset->appendChild($label);

		// Show page # of results:
		$label = Widget::Label(__('Show page of results'));
		$input = Widget::Input('fields[pagination][page]', $this->datasource['pagination']['page']);

		$label->appendChild($input);

		if (isset($errors->{'pagination.page'})) {
			$label = Widget::wrapFormElementWithError($label, $errors->{'pagination.page'});
		}

		$fieldset->appendChild($label);

		$fieldset->appendChild(Widget::Input('fields[pagination][append]', 'no', 'hidden'));

		$label = Widget::Label(__('Append pagination data'));
		$input = Widget::Input('fields[pagination][append]', 'yes', 'checkbox');

		if ($this->datasource['pagination']['append']) {
			$input->setAttribute('checked', 'checked');
		}

		$label->prependChild($input);
		$fieldset->appendChild($label);
		$right->appendChild($fieldset);

	//	Output options ----------------------------------------------------

		$fieldset = Widget::Fieldset(__('Output Options'));

		$context_content = $page->createElement('div');
		$fieldset->appendChild($context_content);

		$right->appendChild($fieldset);
		$layout->appendTo($wrapper);

	//	Build contexts ----------------------------------------------------

		foreach ($field_groups as $section_handle => $section_data) {
			$section = $section_data['section'];
			$section_active = ($this->datasource['section'] == $section_handle);
			$filter_data = $this->datasource['filters'];
			$fields = [];
			$duplicator = new Duplicator(__('Add Filter'));
			$duplicator->addClass('filtering-duplicator context context-' . $section_handle);

			// System ID template:
			$item = $duplicator->createTemplate(__('System ID'));

			$type_label = Widget::Label(__('Type'));
			$type_label->setAttribute('class', 'small');
			$type_label->appendChild(Widget::Select(
				'type',
				array(
					array('is', false, 'Is'),
					array('is-not', false, 'Is not')
				)
			));

			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Input('value'));
			$label->appendChild(Widget::Input(
				'element-name', 'system:id', 'hidden'
			));

			$item->appendChild(Widget::Group(
				$type_label, $label
			));

			// Field templates:
			if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
				foreach ($section_data['fields'] as $field) {
					if (!$field->canFilter()) continue;

					$element_name = $field->{'element-name'};
					$fields[$element_name] = $field;

					$item = $duplicator->createTemplate($field->name, $field->name());
					$field->displayDatasourceFilterPanel(
						$item, null, null
					);
				}
			}

			// Field isntances:
			if (is_array($filter_data) && !empty($filter_data)) {
				foreach ($filter_data as $filter) {
					if (isset($fields[$filter['element-name']])) {
						$element_name = $filter['element-name'];
						$field = $fields[$element_name];
						$item = $duplicator->createInstance($field->{'publish-label'}, $field->name());

						$field->displayDatasourceFilterPanel(
							$item, $filter, $errors->$element_name
						);
					}

					else if ($filter['element-name'] == 'system:id') {
						$item = $duplicator->createInstance(__('System ID'));

						$type_label = Widget::Label(__('Type'));
						$type_label->appendChild(Widget::Select(
							'type',
							array(
								array('is', false, 'Is'),
								array('is-not', $filter['type'] == 'is-not', 'Is not')
							)
						));

						$label = Widget::Label(__('Value'));
						$label->appendChild(Widget::Input(
							"value", $filter['value']
						));

						$label->appendChild(Widget::Input(
							'element-name', 'system:id', 'hidden'
						));

						$item->appendChild(Widget::Group(
							$type_label, $label
						));
					}
				}
			}

			$duplicator->appendTo($container_filter_results);

			// Select boxes:
			$sort_by_options = [
				[
					'system:id',
					($section_active && $this->datasource['sorting']['field'] == 'system:id'),
					__('Entry ID')
				],
				[
					'system:creation-date',
					($section_active && $this->datasource['sorting']['field'] == 'system:creation-date'),
					__('Entry Creation Date')
				],
				[
					'system:modification-date',
					($section_active && $this->datasource['sorting']['field'] == 'system:modification-date'),
					__('Entry Modification Date')
				],
			];

			$options_parameter_output = array(
				[
					'system:id',
					($section_active && $this->datasource['parameters']->containsInstanceOf('Embark\CMS\Fields\IdParameter')),
					__('$ds-?.system.id')
				],
				[
					'system:creation-date',
					($section_active && $this->datasource['parameters']->containsInstanceOf('Embark\CMS\Fields\CreationDateParameter')),
					__('$ds-?.system.creation-date')
				],
				[
					'system:modification-date',
					($section_active && $this->datasource['parameters']->containsInstanceOf('Embark\CMS\Fields\ModificationDateParameter')),
					__('$ds-?.system.modification-date')
				],
				[
					'system:user',
					($section_active && $this->datasource['parameters']->containsInstanceOf('Embark\CMS\Fields\UserParameter')),
					__('$ds-?.system.user')
				]
			);
			$included_elements_options = [
				[
					'system:creation-date',
					($section_active && $this->datasource['elements']->containsInstanceOf('Embark\CMS\Fields\CreationDateElement')),
					__('Entry Creation Date')
				],
				[
					'system:modification-date',
					($section_active && $this->datasource['elements']->containsInstanceOf('Embark\CMS\Fields\ModificationDateElement')),
					__('Entry Modification Date')
				],
				[
					'system:user',
					($section_active && $this->datasource['elements']->containsInstanceOf('Embark\CMS\Fields\UserElement')),
					__('Entry User')
				]
			];

			if (is_array($section_data['fields']) && !empty($section_data['fields'])) {
				foreach ($section_data['fields'] as $field) {
					$field_handle = $field->{'element-name'};
					$field_label = $field->{'publish-label'};
					$modes = $field->fetchIncludableElements();

					if ($field->isSortable()) {
						$sort_by_options[] = array(
							$field_handle,
							($section_active && $field_handle == $this->datasource['sorting']['field']),
							$field_label
						);
					}

					if ($field->allowDatasourceParamOutput()) {
						$options_parameter_output[] = [
							$field_handle,
							(
								$section_active
								&& $this->datasource['parameters']->containsField($field_handle)
							),
							__('$ds-?.%s', array($field_handle))
						];
					}

					if (is_array($modes)) foreach ($modes as $field_mode) {
						$included_elements_options[] = [
							$field_mode['handle'],
							(
								$section_active
								&& $this->datasource['elements']->containsInstanceOfField($field_mode['type'], $field_handle)
							),
							$field_mode['name'] . (
								isset($field_mode['mode'])
									? sprintf(" (%s)", $field_mode['mode'])
									: NULL
							)
						];
					}
				}
			}

			$label = Widget::Label(__('Sort By'));
			$label->setAttribute('class', 'context context-' . $section_handle);

			$label->appendChild(Widget::Select('fields[sort-field]', $sort_by_options, array('class' => 'filtered')));
			$container_sort_by->appendChild($label);

			$param_label = Widget::Label(__('Parameter Output'));

			$select = Widget::Select('fields[parameter-output][]', $options_parameter_output);
			$select->setAttribute('class', 'filtered');
			$select->setAttribute('multiple', 'multiple');

			$param_label->appendChild($select);

			$include_label = Widget::Label(__('Included Fields'));

			$select = Widget::Select('fields[included-elements][]', $included_elements_options);
			$select->setAttribute('class', 'filtered');
			$select->setAttribute('multiple', 'multiple');

			$include_label->appendChild($select);

			$group = Widget::Group($param_label, $include_label);
			$group->setAttribute('class', 'group context context-' . $section_handle);

			$context_content->parentNode->insertBefore($group, $context_content);
		}

		$context_content->remove();
	}

	protected function appendCondition(Duplicator $duplicator, $condition = array()) {
		$document = $duplicator->ownerDocument;

		if (empty($condition)) {
			$item = $duplicator->createTemplate(__('Don\'t Execute When'));
		}

		else {
			$item = $duplicator->createInstance(__('Don\'t Execute When'));
		}

		if (!isset($condition['parameter'])) {
			$condition['parameter'] = null;
		}

		if (!isset($condition['logic'])) {
			$condition['logic'] = 'empty';
		}

		$group = $document->createElement('div');
		$group->setAttribute('class', 'group double');

		// Parameter
		$label = $document->createElement('label', __('Parameter'));
		$label->appendChild(Widget::input('fields[conditions][parameter][]', $condition['parameter']));
		$group->appendChild($label);

		// Logic
		$label = $document->createElement('label', __('Logic'));
		$label->appendChild(Widget::select('fields[conditions][logic][]', array(
			array('empty', ($condition['logic'] == 'empty'), __('is empty')),
			array('set', ($condition['logic'] == 'set'), __('is set'))
		), array('class' => 'filtered')));
		$group->appendChild($label);

		$group->appendChild($label);
		$item->appendChild($group);
	}

	public function prepare(array $data = null) {
		if ($data) {
			$this->datasource['section'] = $data['section'];

			// $this->parameters()->conditions = $this->parameters()->filters = array();

			// if(isset($data['conditions']) && is_array($data['conditions'])){
			// 	foreach($data['conditions']['parameter'] as $index => $parameter){
			// 		$this->parameters()->conditions[$index] = array(
			// 			'parameter' => $parameter,
			// 			'logic' => $data['conditions']['logic'][$index]
			// 		);
			// 	}
			// }

			// if(isset($data['filters']) && is_array($data['filters'])){
			// 	$this->parameters()->filters = $data['filters'];
			// }

			$this->datasource['pagination'] = [];

			exit;

			$this->parameters()->{'append-pagination'} = (isset($data['append-pagination']) && $data['append-pagination'] == 'yes');
			$this->parameters()->{'append-sorting'} = (isset($data['append-sorting']) && $data['append-sorting'] == 'yes');

			if(isset($data['sort-field'])) $this->parameters()->{'sort-field'} = $data['sort-field'];
			if(isset($data['sort-order'])) $this->parameters()->{'sort-order'} = $data['sort-order'];
			if(isset($data['limit'])) $this->parameters()->{'limit'} = $data['limit'];
			if(isset($data['page'])) $this->parameters()->{'page'} = $data['page'];

			if(isset($data['included-elements'])){
				$this->parameters()->{'included-elements'} = (array)$data['included-elements'];
			}

			if(isset($data['parameter-output'])){
				$this->parameters()->{'parameter-output'} = (array)$data['parameter-output'];
			}

			// Calculate dependencies
			$this->parameters()->{'dependencies'} = array();
			if(preg_match_all('/\$ds-([^\s\/?*:;{},\\\\"\'\.]+)/i', serialize($this->parameters()), $matches) > 0){
				$this->parameters()->{'dependencies'} = array_unique($matches[1]);
			}
		}
	}
}