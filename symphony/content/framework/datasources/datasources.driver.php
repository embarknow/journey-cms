<?php

	/**
	* DatasourcesDriver class...
	*/

	Class DatasourcesDriver {

		public $url;
		public $view;

		public function __construct() {
			$this->view = Controller::instance()->View;
			$this->url = Controller::instance()->url;

			$this->setTitle();
		}

		public function setTitle() {
			$this->view->title = __('Data Sources');
		}

		public function registerActions() {

			$actions = array(
				array(
					'name'		=> __('Create New'),
					'type'		=> 'new',
					'callback'	=> $url . '/new'
				),
				array(
					'name'		=> __('Filter'),
					'type'		=> 'tool'
				)
			);

			foreach($actions as $action) {
				$this->view->registerAction($action);
			}
		}

		public function registerDrawer() {
			// Do stuff
		}

		public function buildDataXML($data) {

		}
	}
