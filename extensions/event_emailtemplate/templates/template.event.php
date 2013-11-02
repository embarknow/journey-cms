<?php
	
	require_once EXTENSIONS . '/event_emailtemplate/lib/class.event.php';
	
	Final Class Event%1$s extends Event_EmailTemplate {
		public function __construct(){
			parent::__construct();
			
			$this->_about = (object)array(
				'name'			=> %2$s,
				'author'		=> (object)array(
					'name'			=> %3$s,
					'website'		=> %4$s,
					'email'			=> %5$s
				),
				'version'		=> %6$s,
				'release-date'	=> %7$s
			);
			
			$this->_parameters = (object)array(
				'root-element' => %8$s,
				'trigger' => %9$s,
				'subject' => %10$s,
				'sender-name' => %11$s,
				'sender-addresses' => %12$s,
				'recipient-addresses' => %13$s,
				'view' => %14$s,
				'parameters' => %15$s
			);
		}

		public function allowEditorToParse() {
			return true;
		}
	}

	return 'Event%1$s';
