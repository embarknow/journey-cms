<?php

	Class Extension_DS_Users implements ExtensionInterface, ExtensionWithIncludesInterface {
		public function about() {
			return (object)array(
				'name'			=> 'Users DataSource',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-02-26',
				'type'			=> array(
					'Data Source', 'Core'
				),
				'author'		=> (object)array(
					'name'			=> 'R&B Creative',
					'website'		=> 'http://www.randb.com.au/',
					'email'			=> 'team@symphony-cms.com'
				),
				'provides'		=> array(
					'datasource_template'
				),
				'description'	=> 'Create data sources from backend user data.'
			);
		}

		public function includeFiles() {
			require_once __DIR__ . '/lib/class.datasource.php';
		}

	/*-------------------------------------------------------------------------
		DataSources:
	-------------------------------------------------------------------------*/

		public function getDataSourceTypes() {
			return array(
				(object)array(
					'class'		=> 'UsersDataSource',
					'name'		=> __('Users')
				)
			);
		}
	}

	return 'Extension_DS_Users';