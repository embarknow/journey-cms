<?php

	class Extension_Field_User implements ExtensionInterface {
		public function about() {
			return (object)array(
				'name'			=> 'User',
				'version'		=> '2.0.0',
				'release-date'	=> '2010-04-20',
				'author'		=> (object)array(
					'name'			=> 'R&B Creative',
					'website'		=> 'http://www.randb.com.au/',
					'email'			=> 'team@symphony-cms.com'
				),
				'type' => array(
					'Field', 'Core'
				),
			);
		}

		public function getFieldTypes() {
			require_once __DIR__ . '/fields/field.user.php';

			return array(
				(object)array(
					'class'		=> 'FieldUser',
					'name'		=> __('User')
				)
			);
		}
	}

	return 'Extension_Field_User';