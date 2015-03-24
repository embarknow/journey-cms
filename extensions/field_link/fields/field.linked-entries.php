<?php

use Embark\CMS\Datasource\Exception as DatabaseException;

	class FieldLinked_Entries extends Field {
		public function __construct() {
			parent::__construct();

			$this->_name = __('Linked Entries');
		}

		public function fetchDataKey() {
			return 'relation_id';
		}

		private function buildFilteredPublishLink($data, $entry_id = null) {
			$bits = explode('::', $this->{'related-field'}, 2);
			$filter = (
				is_null($entry_id) === false
					? sprintf('?filter=%s:%d', $bits[1], $entry_id)
					: null
			);

			return Widget::Anchor(
				(string)max(0, $data['count']),
				sprintf('%s/publish/%s/%s', ADMIN_URL, $bits[0], $filter)
			);
		}

		public function prepareTableValue($data, DOMElement $link = null, Entry $entry = null) {
			$entry_id = (
				is_null($entry) === false
					? $entry->id
					: null
			);

			return parent::prepareTableValue(
				(object)array('value' => (string)max(0, $data['count'])),
				(
					is_null($link)
						? $this->buildFilteredPublishLink($data, $entry_id)
						: $link
				)
			);
		}

		public function getParameterOutputValue($data, Entry $entry = null) {
			if (
				is_array($data) === false
				|| empty($data)
				|| isset($data['linked-entries']) === false
				|| empty($data['linked-entries'])
			) {
				return;
			}

			$result = array();

			foreach ($data['linked-entries'] as $value) {
				if (is_null($value)) continue;

				$result[] = $value;
			}

			return $result;
		}

		public function appendFormattedElement(DOMElement $wrapper, $data, $encode = false, $mode = null, Entry $entry = null) {
			if (is_array($data) === false) {
				$data = array($data);
			}

			if (empty($data) || is_null($data[0]->relation_id)) {
				return;
			}

			$list = $wrapper->ownerDocument->createElement($this->{'element-name'});
			$list->setAttribute('count', (string)count($data));

			$bits = explode('::', $this->{'related-field'}, 2);
			$list->setAttribute('section', $bits[0]);
			$list->setAttribute('field', $bits[1]);

			if (is_null($mode) || $mode !== 'entry-count-only') {
				foreach ($data as $key => $value) {
					$list->appendChild($wrapper->ownerDocument->createElement('item', $value->relation_id));
				}
			}

			$wrapper->appendChild($list);
		}

		private function findLinkedEntries($entry) {
			$bits = explode('::', $this->{'related-field'}, 2);

			$result = Symphony::Database()->query(
				'SELECT `entry_id` AS `id` FROM `data_%s_%s` WHERE `relation_id` = %d',
				array(
					$bits[0], $bits[1], $entry->id
				)
			);

			return $result;
		}

		public function loadDataFromDatabase(Entry $entry, $expect_multiple = false) {
			// Find linked entry IDs
			$linked = $this->findLinkedEntries($entry);
			return array('count' => $linked->length(), 'linked-entries' => ($linked->length() > 0 ? (array)$linked->resultColumn('id') : NULL));
		}

		public function loadDataFromDatabaseEntries($section, $entry_ids) {
			$bits = explode('::', $this->{'related-field'}, 2);

			try {
				$result = array();
				$rows = Symphony::Database()->query(
					"SELECT * FROM `data_%s_%s` WHERE `relation_id` IN (%s)",
					array(
						$bits[0], $bits[1],	implode(',', $entry_ids)
					)
				);

				foreach ($rows as $r) {
					$result[] = $r;
				}

				return $result;
			}

			catch (DatabaseException $e) {
				return array(
					'count' =>			0,
					'linked-entries' =>	0
				);
			}
		}

		public function processData($data, Entry $entry = null) {
			return null;
		}

		public function saveData(MessageStack $errors, Entry $entry, $data = null) {
			return self::STATUS_OK;
		}

		public function create(){
			// This field does not required a database
			// table. It looks for things that link to it
			return true;
		}

		public function displayPublishPanel(SymphonyDOMElement $wrapper, MessageStack $errors, Entry $entry = null, $data = null) {
			// Not sure if we need a publish panel
			return;

			$document = $wrapper->ownerDocument;

			$label = Widget::Label(
				(isset($this->{'publish-label'}) && strlen(trim($this->{'publish-label'})) > 0
					? $this->{'publish-label'}
					: $this->name)
			);

			$count = $document->createElement('p');
			$count->appendChild($this->prepareTableValue($data, null, $entry));
			$label->appendChild($count);

			$wrapper->appendChild($label);
		}

		public function displaySettingsPanel(SymphonyDOMElement $wrapper, MessageStack $errors) {
			parent::displaySettingsPanel($wrapper, $errors);

			$document = $wrapper->ownerDocument;

			$label = Widget::Label(__('Linked Field'));

			$options = array(
				array(null, false, null)
			);

			foreach (new SectionIterator as $section) {
				if (
					is_array($section->fields) === false
					|| $section->handle == $this->section
				) {
					continue;
				}

				$fields = array();

				foreach ($section->fields as $field) {
					if ($field->type != 'link') continue;

					$found = false;

					foreach ($field->{'related-fields'} as $index => $f) {
						if (
							isset($this->section)
							&& empty($this->section) === false
							&& $f[0] == $this->section
						) {
							$fields[] = array(
								$section->handle . '::' .$field->{'element-name'},
								(isset($this->{'related-field'}["{$section->handle}::" . $field->{'element-name'}])),
								$field->name
							);
						}
					}
				}

				if (empty($fields) === false) {
					$options[] = array(
						'label' =>		$section->name,
						'options' =>	$fields
					);
				}
			}

			$label->appendChild(Widget::Select('related-field', $options));

			if (isset($errors->{'related-field'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'related-field'});
			}

			$wrapper->appendChild($label);

			$options_list = $wrapper->ownerDocument->createElement('ul');
			$options_list->setAttribute('class', 'options-list');

			$this->appendShowColumnCheckbox($options_list);
			$wrapper->appendChild($options_list);

		}

		public function fetchIncludableElements() {
			return array(
				array(
					'handle' =>		$this->{'element-name'},
					'name' =>		$this->name,
					'mode' =>		null
				),
				array(
					'handle' =>		$this->{'element-name'} . ': entry-count-only',
					'name' =>		$this->name,
					'mode' =>		'Entry Count Only'
				)
			);
		}
	}

	return 'FieldLinked_Entries';