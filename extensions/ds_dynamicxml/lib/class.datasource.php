<?php

	require_once LIB . '/class.cache.php';
	require_once LIB . '/class.xslproc.php';
	require_once LIB . '/class.datasource.php';
	require_once LIB . '/class.gateway.php';

	Class DynamicXMLDataSource extends DataSource {
		public function __construct(){
			$this->_about = new StdClass;
			$this->_parameters = (object)array(
				'timeout' => 6,
				'cache-lifetime' => 60,
				'automatically-discover-namespaces' => 'yes',
				'namespaces' => array(),
				'url' => NULL,
				'xpath' => '*',
				'root-element' => NULL,
			);
		}

		public function getType() {
			return 'DynamicXMLDataSource';
		}

		public function getTemplate(){
			return EXTENSIONS . '/ds_dynamicxml/templates/datasource.php';
		}

	/*-----------------------------------------------------------------------*/

		public function prepare(array $data = null) {
			if(!is_null($data)){
				if(isset($data['about']['name'])) $this->about()->name = $data['about']['name'];

				$this->parameters()->namespaces = array();

				if(is_array($data['namespaces']) && !empty($data['namespaces'])) {
					foreach($data['namespaces']['name'] as $index => $name) {
						if(!strlen(trim($name)) > 0) continue;

						$this->parameters()->namespaces[$index] = array(
							'name' => $name,
							'uri' => $data['namespaces']['uri'][$index]
						);
					}
				}

				if(isset($data['url'])) $this->parameters()->url = $data['url'];
				if(isset($data['xpath'])) $this->parameters()->xpath = $data['xpath'];
				if(isset($data['cache-lifetime'])) $this->parameters()->{'cache-lifetime'} = $data['cache-lifetime'];
				if(isset($data['timeout'])) $this->parameters()->{'timeout'} = $data['timeout'];

				// Namespaces ---------------------------------------------------------

				if(isset($data['automatically-discover-namespaces'])) {
					$this->parameters()->{'automatically-discover-namespaces'} = $data['automatically-discover-namespaces'];

					if ($data['automatically-discover-namespaces'] == 'yes') {
						$gateway = new Gateway();
						$gateway->init();
						$gateway->setopt('URL', $this->parameters()->url);
						$gateway->setopt('TIMEOUT', $this->parameters()->timeout);
						$result = $gateway->exec();

						preg_match_all('/xmlns:([a-z][a-z-0-9\-]*)="([^\"]+)"/i', $result, $matches);

						if (isset($matches[2][0])) {
							$namespaces = array();

							if (!is_array($data['namespaces'])) {
								$data['namespaces'] = array();
							}

							foreach ($data['namespaces'] as $namespace) {
								$namespaces[] = $namespace['name'];
								$namespaces[] = $namespace['uri'];
							}

							foreach ($matches[2] as $index => $uri) {
								$name = $matches[1][$index];

								// Duplicate Namespaces
								if (in_array($name, $namespaces) or in_array($uri, $namespaces)) continue;
								if (General::in_array_multi($name, $this->parameters()->namespaces)) continue;

								$namespaces[] = $name;
								$namespaces[] = $uri;

								$this->parameters()->namespaces[$index] = array(
									'name'	=> $name,
									'uri'	=> $uri
								);
							}
						}
					}
				}
			}
		}

		public function view(SymphonyDOMElement $wrapper, MessageStack $errors) {
			$page = $wrapper->ownerDocument;
			$page->insertNodeIntoHead($page->createScriptElement(URL . '/extensions/ds_sections/assets/view.js'), 55533140);

			$layout = new Layout();
			$left = $layout->createColumn(Layout::SMALL);
			$right = $layout->createColumn(Layout::LARGE);

		//	Essentials --------------------------------------------------------

			$fieldset = Widget::Fieldset(__('Essentials'));

			// Name:
			$label = Widget::Label(__('Name'));
			$input = Widget::Input('fields[about][name]', General::sanitize($this->about()->name));
			$label->appendChild($input);

			if (isset($errors->{'about::name'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'about::name'});
			}

			$fieldset->appendChild($label);

		//	Source ------------------------------------------------------------

			$label = Widget::Label(__('Source URL'));
			$label->appendChild(Widget::Input(
				'fields[url]', General::sanitize($this->parameters()->url)
			));

			if (isset($errors->url)) {
				$label = Widget::wrapFormElementWithError($label, $errors->url);
			}

			$fieldset->appendChild($label);

			$fragment = $page->createDocumentFragment();
			$fragment->appendXML(__('Use <code>{$param}</code> syntax to specify dynamic portions of the URL.'));

			$fieldset->appendChild(
				$page->createElement('p', $fragment, array(
					'class' => 'help'
				))
			);

			$left->appendChild($fieldset);

		//	Timeouts ------------------------------------------------------------

			$fieldset = Widget::Fieldset(__('Time Limits'));

			$label = Widget::Label(__('Cache Limit'));
			$label->appendChild(Widget::Input(
				'fields[cache-lifetime]', max(0, intval($this->parameters()->{'cache-lifetime'})))
			);

			if (isset($errors->{'cache-lifetime'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'cache-lifetime'});
			}

			$fieldset->appendChild($label);
			$fragment = $page->createDocumentFragment();
			$fragment->appendXML(__('How often to refresh the cache.'));

			$fieldset->appendChild(
				$page->createElement('p', $fragment, array(
					'class' => 'help'
				))
			);


			$label = Widget::Label(__('Gateway Timeout'));
			$label->appendChild(Widget::Input(
				'fields[timeout]', max(1, intval($this->parameters()->{'timeout'})))
			);

			if(isset($errors->{'timeout'})){
				$label = Widget::wrapFormElementWithError($label, $errors->{'timeout'});
			}
			$fieldset->appendChild($label);
			$fragment = $page->createDocumentFragment();
			$fragment->appendXML(__('How long to wait for a response.'));

			$fieldset->appendChild(
				$page->createElement('p', $fragment, array(
					'class' => 'help'
				))
			);


			$left->appendChild($fieldset);

		//	Included Elements

			$fieldset = Widget::Fieldset(__('XML Processing'));
			$label = Widget::Label(__('Included Elements'));
			$label->appendChild(Widget::Input('fields[xpath]', General::sanitize($this->parameters()->xpath)));

			if(isset($errors->xpath)){
				$label = Widget::wrapFormElementWithError($label, $errors->xpath);
			}

			$fieldset->appendChild($label);

			$help = Symphony::Parent()->Page->createElement('p');
			$help->setAttribute('class', 'help');
			$help->setValue(__('Use an XPath expression to select which elements from the source XML to include.'));
			$fieldset->appendChild($help);

			$right->appendChild($fieldset);

		//	Namespace Declarations
			$fieldset = Widget::Fieldset(__('Namespace Declarations'), $page->createElement('em', 'Optional'));

			$duplicator = new Duplicator(__('Add Namespace'));
			$this->appendNamespace($duplicator);

			if(is_array($this->parameters()->namespaces)){
				foreach($this->parameters()->namespaces as $index => $namespace) {
					$this->appendNamespace($duplicator, $namespace);
				}
			}
			$duplicator->appendTo($fieldset);

			$right->appendChild($fieldset);
			$layout->appendTo($wrapper);
		}

		protected function appendNamespace(Duplicator $duplicator, array $namespace=NULL) {
			$document = $duplicator->ownerDocument;

			if (is_null($namespace)) {
				$item = $duplicator->createTemplate(__('Namespace'));
			}

			else {
				$item = $duplicator->createInstance(__('Namespace'));
			}

			$group = $document->createElement('div');
			$group->setAttribute('class', 'group double');

			// Name
			$label = Widget::Label(__('Name'));
			$input = Widget::Input('fields[namespaces][name][]', $namespace['name']);
			if(!is_null($namespace) && isset($namespace['name'])) {
				$input->setAttribute("value", $namespace['name']);
			}
			$label->appendChild($input);
			$group->appendChild($label);

			// URI
			$label = Widget::Label(__('URI'));
			$input = Widget::Input('fields[namespaces][uri][]');
			if(!is_null($namespace) && isset($namespace['uri'])) {
				$input->setAttribute("value", $namespace['uri']);
			}
			$label->appendChild($input);
			$group->appendChild($label);
			$item->appendChild($group);

		}

		public function save(MessageStack $errors){
			if(strlen(trim($this->parameters()->url)) == 0){
				$errors->append('url', __('This is a required field'));
			}

			if(strlen(trim($this->parameters()->xpath)) == 0){
				$errors->append('xpath', __('This is a required field'));
			}

			//	Cache Lifetime
			if(!is_numeric($this->parameters()->{'cache-lifetime'})){
				$errors->append('cache-lifetime', __('Must be a valid number'));
			}

			elseif($this->parameters()->{'cache-lifetime'} <= 0){
				$errors->append('cache-lifetime', __('Must be greater than zero'));
			}

			else{
				$this->parameters()->{'cache-lifetime'} = (int)$this->parameters()->{'cache-lifetime'};
			}

			//	Timeout
			if(!is_numeric($this->parameters()->{'timeout'})){
				$errors->append('timeout', __('Must be a valid number'));
			}

			elseif($this->parameters()->{'timeout'} <= 0){
				$errors->append('timeout', __('Must be greater than zero'));
			}

			else{
				$this->parameters()->{'timeout'} = (int)$this->parameters()->{'timeout'};
			}

			return parent::save($errors);
		}

	/*-----------------------------------------------------------------------*/

		public function render(Register $ParameterOutput){
			$result = new XMLDocument;
			$root = $result->createElement($this->parameters()->{'root-element'});

			if (isset($this->parameters()->url)) {
				$this->parameters()->url = self::replaceParametersInString($this->parameters()->url, $ParameterOutput);
			}

			if (isset($this->parameters()->xpath)) {
				$this->parameters()->xpath = self::replaceParametersInString($this->parameters()->xpath, $ParameterOutput);
			}

			$cache_id = md5(
				$this->parameters()->url
				. serialize($this->parameters()->namespaces)
				. $this->parameters()->xpath
			);

			$cache = new Cache(
				Cache::SOURCE_EXTENSION, 'ds_dynamicxml',
				$this->parameters()->{'cache-lifetime'} * 60
			);
			$hasFreshData = false;
			$hasCachedData = isset($cache->{$cache_id});
			$creation = DateTimeObj::get('c');

			if(isset($this->parameters()->timeout)){
				$timeout = (int)max(1, $this->parameters()->timeout);
			}

			if ($hasCachedData === false) {
				$start = microtime(true);

				$ch = new Gateway;

				$ch->init();
				$ch->setopt('URL', $this->parameters()->url);
				$ch->setopt('TIMEOUT', $this->parameters()->timeout);
				$xml = $ch->exec();
				$hasFreshData = true;

				$end = microtime(true) - $start;

				$info = $ch->getInfoLast();

				$xml = trim($xml);
				$knownType = preg_match('/(xml|plain|text)/i', $info['content_type']);

				if ((integer)$info['http_code'] != 200 || $knownType === false) {
					$hasFreshData = false;
				}

				if ($hasFreshData === false && $hasCachedData === false) {
					$root->setAttribute('valid', 'false');

					if ($end > $timeout) {
						$root->appendChild(
							$result->createElement('error',
								sprintf('Request timed out. %d second limit reached.', $timeout)
							)
						);
					}

					else {
						$root->appendChild(
							$result->createElement('error', sprintf(
								'Status code %d was returned. Content-type: %s',
								$info['http_code'], $info['content_type']
							))
						);
					}

					return $result;
				}

				$validXML = General::validateXML($xml, $errors);

				if (strlen($xml) > 0 && $validXML === false) {
					$hasFreshData = false;
				}

				if ($hasFreshData === false && $hasCachedData === false) {
					$root->setAttribute('valud', 'false');
					$root->appendChild(
						$root->createElement('error', __('XML returned is invalid.'))
					);

					return $result;
				}

				if (strlen($xml) == 0) {
					return $this->emptyXMLSet($root);
				}
			}

			else {
				$xml = $cache->{$cache_id};
			}

			//XPath Approach, saves Transforming the Document.
			$xDom = new XMLDocument;
			$xDom->loadXML($xml);

			if ($xDom->hasErrors()) {
				$root->setAttribute('valid', 'false');
				$root->appendChild(
					$result->createElement('error', __('XML returned is invalid.'))
				);

				$messages = $result->createElement('messages');

				foreach($xDom->getErrors() as $e){
					if(strlen(trim($e->message)) == 0) continue;
					$messages->appendChild(
						$result->createElement('item', General::sanitize($e->message))
					);
				}
				$root->appendChild($messages);
				$result->appendChild($root);

				return $result;
			}

			$xpath = new DOMXPath($xDom);

			## Namespaces
			if (is_array($this->parameters()->namespaces) && !empty($this->parameters()->namespaces)) {
				foreach($this->parameters()->namespaces as $index => $namespace) {
					$xpath->registerNamespace($namespace['name'], $namespace['uri']);
				}
			}

			$xpath_list = $xpath->query($this->parameters()->xpath);

			foreach ($xpath_list as $node) {
				if ($node instanceof XMLDocument) {
					$root->appendChild(
						$result->importNode($node->documentElement, true)
					);
				}

				else {
					$root->appendChild(
						$result->importNode($node, true)
					);
				}
			}

			$root->setAttribute('status', (
				$hasFreshData === true
					? 'fresh'
					: 'stale'
			));
			$root->setAttribute('creation', $creation);

			if ($hasFreshData) {
				try {
					$cache->{$cache_id} = $xml;
				}

				catch (Exception $e) {
					Symphony::$Log->writeToLog(sprintf(
						'Unable to write datasource cache to disk for %s.',
						get_class($this)
					));
				}
			}

			$result->appendChild($root);

			return $result;
		}

		public function prepareSourceColumnValue() {

			return Widget::TableData(
				Widget::Anchor(@parse_url($this->parameters()->url, PHP_URL_HOST), $this->parameters()->url, array(
					'title' => $this->parameters()->url,
					'rel' => 'external'
				))
			);

		}
	}
