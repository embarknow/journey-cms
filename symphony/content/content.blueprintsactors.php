<?php

use Embark\CMS\SystemDateTime;
use Embark\CMS\Actors\DatasourceInterface;
use Embark\CMS\Actors\SectionDatasource;

	require_once(LIB . '/class.administrationpage.php');
	require_once(LIB . '/class.datasource.php');
	//require_once(LIB . '/class.sectionmanager.php');
	require_once(LIB . '/class.messagestack.php');
	require_once(LIB . '/class.duplicator.php');

	Class ContentBlueprintsActors extends AdministrationPage{

		protected $errors;
		protected $fields;
		protected $editing;
		protected $failed;
		protected $datasource;
		protected $handle;
		protected $status;
		protected $type;
		protected $types;

		protected static $_loaded_views;

		public function __construct()
		{
			parent::__construct();

			$this->errors = new MessageStack;
			$this->fields = array();
			$this->editing = $this->failed = false;
			$this->actor = $this->handle = $this->status = $this->type = null;
			$this->types = [];

            $extensions = new ExtensionQuery();
            $extensions->setFilters([
                    ExtensionQuery::TYPE =>         'Actors',
                    ExtensionQuery::STATUS =>       Extension::STATUS_ENABLED
            ]);

            foreach ($extensions as $extension) {
                    if (!method_exists($extension, 'getActorTypes')) continue;

                    foreach ($extension->getActorTypes() as $type) {
                            $this->types[get_class($type)] = $type;
                    }
            }

			if (empty($this->types)) {
				$this->alerts()->append(
					__(
						'There are no Actor types currently available. You will not be able to create or edit Actors.'
					),
					AlertStack::ERROR
				);
			}
		}

		public function __viewIndex()
		{
			// This is the 'correct' way to append a string containing an entity
			$title = $this->createElement('title');
			$title->appendChild($this->createTextNode(__('Symphony') . ' '));
			$title->appendChild($this->createEntityReference('ndash'));
			$title->appendChild($this->createTextNode(' ' . __('Actors')));
			$this->insertNodeIntoHead($title);

			$this->appendSubheading(__('Actors'), Widget::Anchor(
				__('Create New'), Administration::instance()->getCurrentPageURL() . '/new/', array(
					'title' => __('Create a new Actor'),
					'class' => 'create button'
				)
			));

			$datasources = new DatasourceIterator;

			$dsTableHead = array(
				array(__('Name'), 'col'),
				array(__('Source'), 'col'),
				array(__('Type'), 'col'),
				array(__('Used By'), 'col')
			);

			$dsTableBody = array();
			$colspan = count($dsTableHead);

			if($datasources->length() <= 0) {
				$dsTableBody = array(Widget::TableRow(
					array(
						Widget::TableData(__('None found.'), array(
								'class' => 'inactive',
								'colspan' => $colspan
							)
						)
					), array(
						'class' => 'odd'
					)
				));
			}

			else {
				//	Load Views so we can determine what Datasources are attached
				if (!self::$_loaded_views) {
					foreach (new ViewIterator as $view) {
						self::$_loaded_views[$view->guid] = array(
							'title' => $view->title,
							'handle' => $view->handle,
							'data-sources' => $view->{'data-sources'}
						);
					}
				}

				foreach ($datasources as $pathname) {
					$ds = DataSource::load($pathname);
					$dsTableBody[] = $row = $this->createElement('tr');

					$ds->appendColumns($row);

					// Used By:
					$fragment_views = $this->createDocumentFragment();

					foreach (self::$_loaded_views as $view) {
						if (is_array($view['data-sources']) && in_array($ds['resource']['handle'], $view['data-sources'])) {
							if ($fragment_views->hasChildNodes()) $fragment_views->appendChild(new DOMText(', '));

							$fragment_views->appendChild(
								Widget::Anchor($view['title'], ADMIN_URL . "/blueprints/views/edit/{$view['handle']}/")
							);
						}
					}

					if (!$fragment_views->hasChildNodes()) {
						$row->appendChild(Widget::TableData(__('None'), [
							'class' => 'inactive'
						]));
					}

					else {
						$row->appendChild(Widget::TableData($fragment_views));
					}

					$row->firstChild->appendChild(Widget::Input("items[{$handle}]", null, 'checkbox'));
				}
			}

			$table = Widget::Table(
				Widget::TableHead($dsTableHead),
				null,
				Widget::TableBody($dsTableBody),
				[
					'id' => 'datasources-list'
				]
			);

			$this->Form->appendChild($table);

			$tableActions = $this->createElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = [
				[null, false, __('With Selected...')],
				['delete', false, __('Delete')]
			];

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);
		}

		public function build($context) {
			if (isset($context[0]) and ($context[0] == 'edit' or $context[0] == 'new')) {
				$context[0] = 'form';
			}

			parent::build($context);
		}

		protected function __prepareForm() {
			$this->editing = isset($this->_context[1]);

			if ($this->editing) {
				$this->handle = $this->_context[1];

				// Status message:
				$callback = Administration::instance()->getPageCallback();

				if (isset($callback['flag']) && !is_null($callback['flag'])) {
					$this->status = $callback['flag'];
				}

				$this->actor = Datasource::loadFromHandle($this->handle);
				$this->type = get_class($this->actor);
				$this->actorForm = $this->actor->createForm();
			}

			else {
				$this->type = $_REQUEST['type'];

				if (!in_array($this->type, array_keys($this->types))) {
					$this->type = current(array_keys($this->types));
				}

				foreach ($this->types as $class => $type) {
					if ($class != $this->type) continue;

					$this->actor = $type;
					$this->actorForm = $this->actor->createForm();

					break;
				}
			}

			$this->actorForm->prepare(
				isset($_POST['fields'])
					? $_POST['fields']
					: NULL
			);
		}

		protected function __actionForm() {
			var_dump('__actionForm'); exit;

			// Delete datasource:
			if ($this->editing && array_key_exists('delete', $_POST['action'])) {
				$this->__actionDelete(array($this->handle), ADMIN_URL . '/blueprints/datasources/');
			}

			// Saving
			try {
				$pathname = $this->actor->save($this->errors);
				$handle = preg_replace('/.php$/i', NULL, basename($pathname));

				redirect(ADMIN_URL . "/blueprints/datasources/edit/{$handle}/:".($this->editing == true ? 'saved' : 'created')."/");
			}

			catch (DatasourceException $e) {
				$this->alerts()->append(
					$e->getMessage(),
					AlertStack::ERROR, $e
				);
			}

			catch (Exception $e) {
				$this->alerts()->append(
					__('An unknown error has occurred. <a class="more">Show trace information.</a>'),
					AlertStack::ERROR, $e
				);
			}
		}

		protected function __viewForm() {
			// Show page alert:
			if ($this->failed) {
				$this->alerts()->append(
					__('An error occurred while processing this form. <a href="#error">See below for details.</a>'),
					AlertStack::ERROR
				);
			}

			else if (!is_null($this->status)) {
				switch ($this->status) {
					case 'saved':
						$this->alerts()->append(
							__(
								'Actor updated at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all</a>',
								array(
									General::getTimeAgo(__SYM_TIME_FORMAT__),
									ADMIN_URL . '/blueprints/datasources/new/',
									ADMIN_URL . '/blueprints/datasources/'
								)
							),
							AlertStack::SUCCESS
						);
						break;

					case 'created':
						$this->alerts()->append(
							__(
								'Actor created at %1$s. <a href="%2$s">Create another?</a> <a href="%3$s">View all</a>',
								array(
									General::getTimeAgo(__SYM_TIME_FORMAT__),
									ADMIN_URL . '/blueprints/datasources/new/',
									ADMIN_URL . '/blueprints/datasources/'
								)
							),
							AlertStack::SUCCESS
						);
						break;
				}
			}

			if (is_null($this->actor['name']) || strlen(trim($this->actor['name'])) == 0) {
				$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(
					__('Symphony'), __('Actors'), __('Untitled')
				)));
				$this->appendSubheading(General::sanitize(__('Actor')));
			}

			else {
				$this->setTitle(__('%1$s &ndash; %2$s &ndash; %3$s', array(
					__('Symphony'), __('Actors'), $this->actor['name']
				)));
				$this->appendSubheading(General::sanitize($this->actor['name']));
			}

			// Track type with a hidden field:
			if ($this->editing || ($this->editing && isset($_POST['type']))) {
				$input = Widget::Input('type', $this->type, 'hidden');
				$this->Form->appendChild($input);
			}

			 // Let user choose type:
			else {
				$header = $this->xpath('//h2')->item(0);
				$options = array();

				foreach ($this->types as $class => $type) {
					$options[] = array($class, ($this->type == $class), $type->getType());
				}

				usort($options, 'General::optionsSort');
				$select = Widget::Select('type', $options);

				$header->prependChild($select);
				$header->prependChild(new DOMText(__('New')));
			}

			$this->actorForm->view($this->Form, $this->errors);

			$actions = $this->createElement('div');
			$actions->setAttribute('class', 'actions');

			$save = Widget::Submit(
				'action[save]', ($this->editing) ? __('Save Changes') : __('Create Actor'),
				array(
					'accesskey' => 's'
				)
			);

			if(!($this->actor instanceof DatasourceInterface)){
				$save->setAttribute('disabled', 'disabled');
			}

			$actions->appendChild($save);

			if ($this->editing == true) {
				$actions->appendChild(
					Widget::Submit(
						'action[delete]', __('Delete'),
						array(
							'class' => 'confirm delete',
							'title' => __('Delete this Actor')
						)
					)
				);
			}

			$this->Form->appendChild($actions);
		}

		protected function __actionDelete(array $datasources, $redirect=NULL) {
			var_dump('__actionDelete'); exit;

			$success = true;

			foreach ($datasources as $handle) {
				try{
					Datasource::delete($handle);
				}
				catch(DatasourceException $e){
					$success = false;
					$this->alerts()->append(
						$e->getMessage(),
						AlertStack::ERROR, $e
					);
				}
				catch(Exception $e){
					$success = false;
					$this->alerts()->append(
						__('An unknown error has occurred. <a class="more">Show trace information.</a>'),
						AlertStack::ERROR, $e
					);
				}

				// TODO: Delete reference from View XML

				/*$sql = "SELECT * FROM `pages` WHERE `data_sources` REGEXP '[[:<:]]".$ds."[[:>:]]' ";
				$pages = Symphony::Database()->fetch($sql);

				if(is_array($pages) && !empty($pages)){
					foreach($pages as $page){

						$page['data_sources'] = preg_replace('/\b'.$ds.'\b/i', '', $page['data_sources']);

						Symphony::Database()->update($page, 'pages', "`id` = '".$page['id']."'");
					}
				}*/
			}

			if($success) redirect($redirect);
		}

		public function __actionIndex() {
			var_dump('__actionIndex'); exit;

			$checked = is_array($_POST['items']) ? array_keys($_POST['items']) : null;

			if(is_array($checked) && !empty($checked)) {
				switch ($_POST['with-selected']) {
					case 'delete':
						$this->__actionDelete($checked, ADMIN_URL . '/blueprints/datasources/');
						break;
				}
			}
		}
	}