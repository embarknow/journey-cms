<?php

	class Extension_Field_Taglist implements ExtensionInterface {
		public function about() {
			return (object)array(
				'name'			=> 'Taglist',
				'version'		=> '2.0.0',
				'release-date'	=> '2010-04-22',
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
			require_once __DIR__ . '/fields/field.taglist.php';

			return array(
				(object)array(
					'class'		=> 'FieldTagList',
					'name'		=> __('Tag List')
				)
			);
		}
	}

	return 'Extension_Field_Taglist';
