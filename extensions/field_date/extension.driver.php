<?php

	class Extension_Field_Date implements ExtensionInterface {
		public function about() {
			return (object)array(
				'name'			=> 'Date',
				'version'		=> '2.0.0',
				'release-date'	=> '2010-02-16',
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
			require_once __DIR__ . '/fields/field.date.php';

			return array(
				(object)array(
					'class'		=> 'FieldDate',
					'name'		=> __('Date')
				)
			);
		}
	}

	return 'Extension_Field_Date';