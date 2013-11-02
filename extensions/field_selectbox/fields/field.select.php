<?php

	Class fieldSelect extends Field {
		function __construct(){
			parent::__construct();
			$this->_name = __('Select Box');
		}

		public function create(){
			return Symphony::Database()->query(sprintf('
				CREATE TABLE IF NOT EXISTS `tbl_data_%s_%s` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned NOT NULL,
					`handle` varchar(255) default NULL,
					`value` varchar(255) default NULL,
					PRIMARY KEY  (`id`),
					KEY `entry_id` (`entry_id`),
					KEY `handle` (`handle`),
					KEY `value` (`value`)
				) ENGINE=MyISAM;',
				$this->section,
				$this->{'element-name'}
			));
		}

		public function canToggleData(){
			return !isset($this->{'allow-multiple-selection'}) ? true : false;
		}

		function allowDatasourceOutputGrouping(){
			## Grouping follows the same rule as toggling.
			return $this->canToggleData();
		}

		function allowDatasourceParamOutput(){
			return true;
		}

		function canFilter(){
			return true;
		}

		public function canImport(){
			return true;
		}

		function canPrePopulate(){
			return true;
		}

		function isSortable(){
			return true;
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

		public function getToggleStates() {
			$values = preg_split('/,\s*/i', $this->{'static-options'}, -1, PREG_SPLIT_NO_EMPTY);

			if ($this->{'dynamic-options'} != '') $this->findAndAddDynamicOptions($values);

			$values = array_map('trim', $values);
			$states = array();

			foreach ($values as $value) {
				$states[$value] = $value;
			}

			return $states;
		}

		function findAndAddDynamicOptions(&$values){
			list($section, $field_handle) = explode("::", $this->{'dynamic-options'});

			if(!is_array($values)) $values = array();

			$result = Symphony::Database()->query("
				SELECT
					DISTINCT `value`
				FROM
					`tbl_data_%s_%s`
				", array($section, $field_handle)
			);

			if($result->valid()) $values = array_merge($values, $result->resultColumn('value'));
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function findDefaultSettings(array &$fields){
			if(!isset($fields['allow-multiple-selection'])) $fields['allow-multiple-selection'] = 'no';
		}

		public function displaySettingsPanel(SymphonyDOMElement $wrapper, MessageStack $messages) {
			parent::displaySettingsPanel($wrapper, $messages);

			$document = $wrapper->ownerDocument;

			$label = Widget::Label(__('Static Options'));
			$label->appendChild($document->createElement('em', __('Optional')));
			$input = Widget::Input('static-options', General::sanitize($this->{'static-options'}));
			$label->appendChild($input);
			$wrapper->appendChild($label);

			$label = Widget::Label(__('Dynamic Options'));

			$options = array(
				array('', false, __('None')),
			);

			foreach (new SectionIterator as $section) {
				if(!is_array($section->fields) || $section->handle == $this->section) continue;

				$fields = array();

				foreach($section->fields as $field) {
					if($field->canPrePopulate()) {
						$fields[] = array(
							$section->handle . '::' .$field->{'element-name'},
							($this->{'dynamic-options'} == $section->handle . '::' .$field->{'element-name'}),
							$field->{'publish-label'}
						);
					}
				}

				if(!empty($fields)) {
					$options[] = array(
						'label' => $section->name,
						'options' => $fields
					);
				}
			}

			$label->appendChild(Widget::Select('dynamic-options', $options));

			if(isset($errors['dynamic-options'])) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $errors['dynamic-options']));
			else $wrapper->appendChild($label);

			$options_list = $document->createElement('ul');
			$options_list->setAttribute('class', 'options-list');

			$this->appendShowColumnCheckbox($options_list);
			$this->appendRequiredCheckbox($options_list);

			## Allow selection of multiple items
			$label = Widget::Label(__('Allow selection of multiple options'));

			$input = Widget::Input('allow-multiple-selection', 'yes', 'checkbox');
			if($this->{'allow-multiple-selection'} == 'yes') $input->setAttribute('checked', 'checked');

			$label->prependChild($input);
			$options_list->appendChild($label);

			$wrapper->appendChild($options_list);

		}

		public function validateSettings(MessageStack $messages, $checkForDuplicates=true){
			if ($this->{'static-options'} == '' && ($this->{'dynamic-options'} == '' || $this->{'dynamic-options'} == 'none')) {
				$messages->{'dynamic-options'} = __('At least one source must be specified, dynamic or static.');
			}

			return parent::validateSettings($messages, $checkForDuplicates);
		}

	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/

		public function prepareTableValue($data, DOMElement $link=NULL){

			if(!is_array($data)){
				$data = array($data);
			}

			$values = array();
			foreach($data as $d){
				$values[] = $d->value;
			}

			return parent::prepareTableValue((object)array('value' => implode(', ', $values)), $link);
		}

		public function displayPublishPanel(SymphonyDOMElement $wrapper, MessageStack $errors, Entry $entry = null, $data = null) {
			if(!is_array($data)){
				$data = array($data);
			}

			$selected = array();
			foreach($data as $d){
				if(!($d instanceof StdClass) || !isset($d->value)) continue;
				$selected[] = $d->value;
			}

			$states = $this->getToggleStates();
			natsort($states);

			$options = array();

			if($this->{'required'} == 'yes') {
				$options[] = array(null, false);
			}

			foreach($states as $handle => $v){
				$options[] = array($handle, in_array($v, $selected), $v);
			}

			$fieldname = 'fields['.$this->{'element-name'}.']';
			if($this->{'allow-multiple-selection'} == 'yes') $fieldname .= '[]';

			$label = Widget::Label(
				(isset($this->{'publish-label'}) && strlen(trim($this->{'publish-label'})) > 0
					? $this->{'publish-label'}
					: $this->name)
			);
			$label->appendChild(Widget::Select($fieldname, $options,
				($this->{'allow-multiple-selection'} == 'yes') ? array('multiple' => 'multiple') : array()
			));

			if ($errors->valid()) {
				$label = Widget::wrapFormElementWithError($label, $errors->current()->message);
			}

			$wrapper->appendChild($label);
		}

		public function toggleEntryData(StdClass $data, $value, Entry $entry=NULL){
			$data['value'] = $newState;
			$data['handle'] = Lang::createHandle($newState);
			return $data;
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function loadDataFromDatabase(Entry $entry, $expect_multiple = false) {
			return parent::loadDataFromDatabase($entry, true);
		}

		public function processData($data, Entry $entry = null) {
			$result = null;

			if ($data !== null) {
				$result = array();

				if (!is_array($data)) $data = array($data);

				foreach ($data as $value) {
					if ($value instanceof StdClass) {
						$value = $value->value;
					}

					if ($value !== null) {
						$result[] = (object)array(
							'handle' =>	Lang::createHandle($value),
							'value' =>	$value
						);
					}
				}
			}

			return $result;
		}

		public function validateData(MessageStack $errors, Entry $entry = null, $data = null) {
			$value = null;

			if (is_array($data) === false) {
				$data = array($data);
			}

			foreach ($data as $item) {
				$value .= $item->value;
			}

			if ($this->required == 'yes' && strlen(trim($value)) == 0) {
				$errors->append(
					null, (object)array(
					 	'message' =>	__("'%s' is a required field.", array($this->{'publish-label'})),
						'code' =>		self::ERROR_MISSING
					)
				);

				return self::STATUS_ERROR;
			}

			return self::STATUS_OK;
		}

		public function saveData(MessageStack $errors, Entry $entry, $data = null) {
			$table = sprintf('tbl_data_%s_%s', $entry->section, $this->{'element-name'});

			// Purge existing values:
			Symphony::Database()->delete($table, array($entry->id), "`entry_id` = %s");

			if ($data === null) return;

			foreach ($data as $item) {
				try {
					Symphony::Database()->insert($table, array(
						'id' =>			null,
						'entry_id' =>	$entry->id,
						'handle' =>		$item->handle,
						'value' =>		$item->value
					));
				}

				catch (DatabaseException $e) {
					return self::STATUS_ERROR;
				}

				catch (Exception $e) {
					return self::STATUS_ERROR;
				}
			}

			return Field::STATUS_OK;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(DOMElement $wrapper, $data, $encode=false, $mode=NULL, Entry $entry=NULL) {
			if (!is_array($data)) $data = array($data);
			if (empty($data) or is_null($data[0]->value)) return;

			$list = $wrapper->ownerDocument->createElement($this->{'element-name'});

			foreach ($data as $d) {
				$item = $wrapper->ownerDocument->createElement('item');
				$item->setValue($d->value);
				$item->setAttribute('handle', $d->handle);
				$list->appendChild($item);
			}

			$wrapper->appendChild($list);
		}

		public function getParameterOutputValue($data, Entry $entry=NULL){
			if(!is_array($data)) $data = array($data);

			$result = array();
			if(!empty($data)) foreach($data as $d) {
				if(is_null($d->value)) continue;

				$result[] = $d->value;
			}

			return $result;
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		public function displayDatasourceFilterPanel(SymphonyDOMElement $wrapper, $data=NULL, MessageStack $errors=NULL){
			parent::displayDatasourceFilterPanel($wrapper, $data, $errors);

			$document = $wrapper->ownerDocument;
			$existing_options = $this->getToggleStates();

			$div = $document->createElement('div');
			$label = $document->xpath('.//label[last()]', $wrapper)->item(0);
			$label->wrapWith($div);

			if (is_array($existing_options) && !empty($existing_options)) {
				$optionlist = $document->createElement('ul');
				$optionlist->setAttribute('class', 'tags');

				foreach ($existing_options as $option) {
					$optionlist->appendChild(
						$document->createElement('li', $option)
					);
				}

				$div->appendChild($optionlist);
			}
		}

	/*-------------------------------------------------------------------------
		Grouping:
	-------------------------------------------------------------------------*/

		public function groupRecords($records){

			if(!is_array($records) || empty($records)) return;

			$groups = array($this->{'element-name'} => array());

			foreach($records as $r){
				$data = $r->getData($this->id);

				$value = $data['value'];
				$handle = Lang::createHandle($value);

				if(!isset($groups[$this->{'element-name'}][$handle])){
					$groups[$this->{'element-name'}][$handle] = array('attr' => array('handle' => $handle, 'value' => $value),
																		 'records' => array(), 'groups' => array());
				}

				$groups[$this->{'element-name'}][$handle]['records'][] = $r;

			}

			return $groups;
		}

	/*-------------------------------------------------------------------------
		Possibly Deprecated:
	-------------------------------------------------------------------------*/

		function fetchAssociatedEntrySearchValue($data){
			if(!is_array($data)) return $data;

			return $data['value'];
		}

		function fetchAssociatedEntryCount($value){
			$result = Symphony::Database()->query("
				SELECT
					`entry_id`
				FROM
					`tbl_entries_data_%d`
				WHERE
					`value` = '%s
				",
				$this->id,
				$value
			);

			return ($result->valid()) ? $result->current->count : false;
		}

		function fetchAssociatedEntryIDs($value){
			$result = Symphony::Database()->query("
				SELECT
					count(*) AS `count`
				FROM
					`tbl_entries_data_%d`
				WHERE
					`value` = '%s
				",
				$this->id,
				$value
			);

			return ($result->valid()) ? $result->resultColumn('entry_id') : false;
		}

		public function getExampleFormMarkup(){
			$states = $this->getToggleStates();

			$options = array();

			foreach($states as $handle => $v){
				$options[] = array($v, NULL, $v);
			}

			$fieldname = 'fields['.$this->{'element-name'}.']';
			if($this->{'allow-multiple-selection'} == 'yes') $fieldname .= '[]';

			$label = Widget::Label($this->{'publish-label'});
			$label->appendChild(Widget::Select($fieldname, $options,
				($this->{'allow-multiple-selection'} == 'yes') ? array('multiple' => 'multiple') : array()
			));

			return $label;
		}
	}

	return 'FieldSelect';