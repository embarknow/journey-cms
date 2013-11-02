<?php

	class Extension_Field_Selectbox implements ExtensionInterface {
		public function about() {
			return (object)array(
				'name'			=> 'Selectbox',
				'version'		=> '2.0.0',
				'release-date'	=> '2010-04-20',
				'author'		=> (object)array(
					'name'			=> 'R&B Creative',
					'website'		=> 'http://www.randb.com.au/',
					'email'			=> 'team@symphony-cms.com'
				),
				'type'			=> array(
					'Field', 'Core'
				),
			);
		}

		public function getFieldTypes() {
			require_once __DIR__ . '/fields/field.select.php';

			return array(
				(object)array(
					'class'		=> 'FieldSelect',
					'name'		=> __('Select Box')
				)
			);
		}
	}

	return 'Extension_Field_Selectbox';