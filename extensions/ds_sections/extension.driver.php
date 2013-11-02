<?php

	class Extension_DS_Sections implements ExtensionInterface {
		public function about() {
			return (object)array(
				'name'			=> 'Sections DataSource and Event',
				'version'		=> '1.0.0',
				'release-date'	=> '2010-03-02',
				'type'			=> array(
					'Data Source', 'Event', 'Core'
				),
				'author'		=> (object)array(
					'name'			=> 'R&B Creative',
					'website'		=> 'http://www.randb.com.au/',
					'email'			=> 'team@symphony-cms.com'
				),
				'provides'		=> array(
					'datasource_template'
				),
				'description'	=> 'Create data sources from an XML string.'
			);
		}

	/*-------------------------------------------------------------------------
		DataSources and Events:
	-------------------------------------------------------------------------*/

		public function getDataSourceTypes() {
			require_once __DIR__ . '/lib/class.datasource.php';

			return array(
				(object)array(
					'class'		=> 'SectionsDataSource',
					'name'		=> __('Sections')
				)
			);
		}

		public function getEventTypes() {
			require_once __DIR__ . '/lib/class.event.php';

			return array(
				(object)array(
					'class'		=> 'SectionsEvent',
					'name'		=> __('Sections')
				)
			);
		}
	}

	return 'Extension_DS_Sections';