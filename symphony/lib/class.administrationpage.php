<?php

use Embark\CMS\Sections\Controller;
use Embark\CMS\Metadata\Types\MenuItem;

	require_once(LIB . '/class.htmldocument.php');
	require_once(LIB . '/class.section.php');
	require_once(LIB . '/class.layout.php');
	require_once(LIB . '/class.alertstack.php');

	Class AdministrationPage extends HTMLDocument{
		public $_navigation;
		public $_context;
		protected $_alerts;

		public function __construct(){
			parent::__construct('1.0', 'utf-8', 'html');
			$this->alerts = new AlertStack;
		}

		public function setTitle($value, $position=null) {
			$doc = new XMLDocument;
			$doc->loadHTML("<title>{$value}</title>");
			$node = $this->importNode($doc->xpath('//title')->item(0), true);
			return $this->insertNodeIntoHead($node, $position);
		}

		public function Context(){
			return $this->_context;
		}

		public function minify(array $files, $output_pathname, $unlink_existing=true){

			if(file_exists($output_pathname) && $unlink_existing === true) unlink($output_pathname);

			foreach($files as $pathname){
				if(!file_exists($pathname) || !is_readable($pathname)) throw new Exception("File '{$pathname}' could not be found, or is not readable.");

				$contents = file_get_contents($pathname);

				if(file_put_contents($output_pathname, $contents . "\n", FILE_APPEND) === false){
					throw new Exception("Could not write to '{$output_pathname}.");
				}
			}
		}

		public function build($context = NULL){
			$this->_context = $context;

			$meta = $this->createElement('meta');
			$this->insertNodeIntoHead($meta);
			$meta->setAttribute('http-equiv', 'Content-Type');
			$meta->setAttribute('content', 'text/html; charset=UTF-8');

			$styles = array(
				ADMIN_URL . '/assets/css/symphony.css',
				ADMIN_URL . '/assets/css/symphony.duplicator.css',
				ADMIN_URL . '/assets/css/symphony.layout.css'
			);

			$scripts = array(
				ADMIN_URL . '/assets/js/jquery.js',
				ADMIN_URL . '/assets/js/jquery-ui.js',
				ADMIN_URL . '/assets/js/symphony.collapsible.js',
				ADMIN_URL . '/assets/js/symphony.orderable.js',
				ADMIN_URL . '/assets/js/symphony.duplicator.js',
				ADMIN_URL . '/assets/js/symphony.layout.js',
				ADMIN_URL . '/assets/js/symphony.tags.js',
				ADMIN_URL . '/assets/js/symphony.selectable.js',
				ADMIN_URL . '/assets/js/symphony.js'
			);

			// Builds a super JS and CSS document
			if (Symphony::Configuration()->main()->admin->{'minify-assets'} == 'yes'){
				if (file_exists(CACHE . '/admin-styles.css')){
					$styles = array(URL . '/manifest/cache/admin-styles.css');
				}

				else {
					try{
						$this->minify(array_map(create_function('$a', 'return DOCROOT . "/symphony/assets/css/" . basename($a);'), $styles), CACHE . '/admin-styles.css');
						$styles = array(URL . '/manifest/cache/admin-styles.css');
					}
					catch(Exception $e){
					}
				}

				if(file_exists(CACHE . '/admin-scripts.js')){
					$scripts = array(URL . '/manifest/cache/admin-scripts.js');
				}
				else{
					try{
						$this->minify(array_map(create_function('$a', 'return DOCROOT . "/symphony/assets/js/" . basename($a);'), $scripts), CACHE . '/admin-scripts.js');
						$scripts = array(URL . '/manifest/cache/admin-scripts.js');
					}
					catch(Exception $e){
					}
				}
			}

			foreach($styles as $pathname){
				$this->insertNodeIntoHead($this->createStylesheetElement($pathname));
			}

			foreach($scripts as $pathname){
				$this->insertNodeIntoHead($this->createScriptElement($pathname));
			}

			###
			# Delegate: InitaliseAdminPageHead
			# Description: Allows developers to insert items into the page HEAD. Use $context['parent']->Page
			#			   for access to the page object
			Extension::notify('InitaliseAdminPageHead', '/administration/');

			$this->Headers->append('Content-Type', 'text/html; charset=UTF-8');

			$this->prepare();

			if(isset($_REQUEST['action'])){
				$this->action();
			}

			## Build the form
			$this->Form = $this->createElement('form');
			$this->Form->setAttribute('action', Administration::instance()->getCurrentPageURL());
			$this->Form->setAttribute('method', 'POST');
			$this->Body->appendChild($this->Form);

			$h1 = $this->createElement('h1');
			$anchor = $this->createElement('a', Symphony::Configuration()->main()->name);
			$anchor->setAttribute('href', rtrim(URL, '/') . '/');
			$h1->appendChild($anchor);
			$this->Form->appendChild($h1);

			$this->appendSession();
			$this->appendNavigation();
			$this->view();

			###
			# Delegate: AppendElementBelowView
			# Description: Allows developers to add items just above the page footer. Use $context['parent']->Page
			#			   for access to the page object
			Extension::notify('AppendElementBelowView', '/administration/');

			$this->appendAlert();
		}

		public function view(){
			$this->__switchboard();
		}

		public function action(){
			$this->__switchboard('action');
		}

		public function prepare(){
			$this->__switchboard('prepare');
		}

		public function __switchboard($type='view'){

			if(!isset($this->_context[0]) || trim($this->_context[0]) == '') $context = 'index';
			else $context = $this->_context[0];

			$function = '__' . $type . ucfirst($context);

			// If there is no view function, throw an error
			if (!is_callable(array($this, $function))){

				if ($type == 'view'){
					throw new AdministrationPageNotFoundException;
				}

				return false;
			}
			$this->$function();
		}

		public function alerts(){
			return $this->alerts;
		}

		public function appendAlert(){
			###
			# Delegate: AppendPageAlert
			# Description: Allows for appending of alerts. Administration::instance()->Page->Alert is way to tell what
			# is currently in the system
			Extension::notify('AppendPageAlert', '/administration/');

			if ($this->alerts()->valid()) {
				$this->alerts()->appendTo($this->Body);
			}
		}

		public function appendSession(){

			$ul = $this->createElement('ul');
			$ul->setAttribute('id', 'session');

			$li = $this->createElement('li');
			$li->appendChild(
				Widget::Anchor(Symphony::User()->getFullName(), ADMIN_URL . '/system/users/edit/' . Symphony::User()->user_id . '/')
			);
			$ul->appendChild($li);

			$li = $this->createElement('li');
			$li->appendChild(
				Widget::Anchor(__('Logout'), ADMIN_URL . '/logout/')
			);
			$ul->appendChild($li);

			###
			# Delegate: AddElementToFooter
			# Description: Add new list elements to the footer
			Extension::notify('AddElementToFooter', '/administration/', array('wrapper' => &$ul));

			$this->Form->appendChild($ul);
		}

		public function appendSubheading($string, $link=NULL){
			$h2 = $this->createElement('h2', $string);
			if(!is_null($link)) $h2->appendChild($link);

			$this->Form->appendChild($h2);
		}

		public function appendButton(DOMElement $item)
		{
			if ($this->evaluate('boolean(h2)', $this->Form)) {
				$h2 = $this->xpath('h2', $this->Form)->item(0);
			}

			else {
				$h2 = $this->createElement('h2', $string);
				$h2->addClass('breadcrumb');
				$this->Form->appendChild($h2);
			}

			$h2->appendChild($item);
		}

		public function appendBreadcrumb($item)
		{
			if ($this->evaluate('boolean(h2)', $this->Form)) {
				$parent = $this->xpath('h2/span[@class = "breadcrumb"]', $this->Form)->item(0);
			}

			else {
				$h2 = $this->createElement('h2', $string);
				$parent = $this->createElement('span');
				$parent->addClass('breadcrumb');
				$h2->appendChild($parent);
				$this->Form->appendChild($h2);
			}

			if (!($item instanceof DOMElement)) {
				$parent->appendChild($this->createElement('span', $item));
			}

			else {
				$span = $this->createElement('span');
				$span->appendChild($item);
				$parent->appendChild($span);
			}
		}

		public function appendNavigation(){

			$nav = $this->getNavigationArray();

			####
			# Delegate: NavigationPreRender
			# Description: Immediately before displaying the admin navigation. Provided with the navigation array
			#              Manipulating it will alter the navigation for all pages.
			# Global: Yes
			Extension::notify('NavigationPreRender', '/administration/', array('navigation' => &$nav));

			$xNav = $this->createElement('ul');
			$xNav->setAttribute('id', 'nav');

			foreach($nav as $n){
				$can_access = true;

				if(!isset($n['visible']) or $n['visible'] != 'no'){

					if($can_access == true) {

						$xGroup = $this->createElement('li', $n['name']);
						$xGroup->setAttribute('id', 'nav-' . Lang::createHandle($n['name']));

						if(isset($n['class']) && trim($n['name']) != '') $xGroup->setAttribute('class', $n['class']);

						$xChildren = $this->createElement('ul');

						$hasChildren = false;

						if(is_array($n['children']) && !empty($n['children'])){
							foreach($n['children'] as $c){

								$can_access_child = true;

								if($c['visible'] != 'no'){

									if($can_access_child == true) {

										$xChild = $this->createElement('li');
										$xChild->appendChild(
											Widget::Anchor($c['name'], ADMIN_URL . $c['link'])
										);
										$xChildren->appendChild($xChild);
										$hasChildren = true;

									}
								}

							}

							if($hasChildren){
								$xGroup->appendChild($xChildren);
								$xNav->appendChild($xGroup);
							}
						}
					}
				}
			}

			$this->Form->appendChild($xNav);
		}

		public function getNavigationArray(){
			if(empty($this->_navigation)) $this->__buildNavigation();
			return $this->_navigation;
		}

		private static function __navigationFindGroupIndex($nav, $name){
			foreach($nav as $index => $item){
				if($item['name'] == $name) return $index;
			}
			return false;
		}

		protected function __buildNavigation(){

			$nav = array();

			$xml = simplexml_load_file(ASSETS . '/navigation.xml');

			foreach($xml->xpath('/navigation/group') as $n){

				$index = (string)$n->attributes()->index;
				$children = $n->xpath('children/item');
				$content = $n->attributes();

				if(isset($nav[$index])){
					do{
						$index++;
					}while(isset($nav[$index]));
				}

				$nav[$index] = array(
					'name' => __(strval($content->name)),
					'index' => $index,
					'children' => array()
				);

				if(strlen(trim((string)$content->limit)) > 0){
					$nav[$index]['limit'] = (string)$content->limit;
				}

				if(count($children) > 0){
					foreach($children as $child){
						$limit = (string)$child->attributes()->limit;

						$item = array(
							'link' => (string)$child->attributes()->link,
							'name' => __(strval($child->attributes()->name)),
							'visible' => ((string)$child->attributes()->visible == 'no' ? 'no' : 'yes'),
						);

						if(strlen(trim($limit)) > 0) $item['limit'] = $limit;

						$nav[$index]['children'][] = $item;
					}
				}
			}

			foreach (Controller::findAll() as $section) {
				// Section doesn't have a menu item, don't append it:
				if (!($section['menu'] instanceof MenuItem)) {
					continue;
				}

				$groupIndex = self::__navigationFindGroupIndex($nav, $section['menu']['name']);

				if (false === $groupIndex) {
					$groupIndex = General::array_find_available_index($nav, 0);

					$nav[$groupIndex] = array(
						'name' =>		$section['menu']['name'],
						'index' =>		$groupIndex,
						'children' =>	[],
						'limit' =>		null
					);
				}

				$nav[$groupIndex]['children'][] = [
					'link' =>		'/publish/' . $section['resource']['handle'],
					'name' =>		$section['name'],
					'order' =>		$section['menu']['order'],
					'type' =>		'section',
					'section' =>	[
										'id' =>		$section['guid'],
										'handle' =>	$section['resource']['handle']
									],
					'visible' =>	'yes'
				];
			}

			$extensions = new ExtensionQuery();
			$extensions->setFilters(array(
				ExtensionQuery::STATUS =>	Extension::STATUS_ENABLED
			));

			foreach ($extensions as $e) {
				if (method_exists($e, 'fetchNavigation') === false) continue;

				$e_navigation = $e->fetchNavigation();

				if (isset($e_navigation) && is_array($e_navigation) && !empty($e_navigation)) {
					foreach ($e_navigation as $item) {
						$type = (
							isset($item['children'])
								? Extension::NAVIGATION_GROUP
								: Extension::NAVIGATION_CHILD
						);

						switch ($type) {
							case Extension::NAVIGATION_GROUP:
								$index = General::array_find_available_index($nav, $item['location']);

								$nav[$index] = array(
									'name' => $item['name'],
									'index' => $index,
									'children' => array(),
									'limit' => (!is_null($item['limit']) ? $item['limit'] : NULL)
								);

								foreach ($item['children'] as $child) {
									if (isset($child['relative']) === false || $child['relative'] == true) {
										$link = '/extension/' . $e->handle . '/' . ltrim($child['link'], '/');
									}

									else {
										$link = '/' . ltrim($child['link'], '/');
									}

									$nav[$index]['children'][] = array(
										'link' =>		$link,
										'name' =>		$child['name'],
										'visible' =>	(
															$child['visible'] == 'no'
																? 'no'
																: 'yes'
														),
										'limit' =>		(
															isset($child['limit'])
																? $child['limit']
																: null
														)
									);
								}

								break;

							case Extension::NAVIGATION_CHILD:
								if (isset($item['relative']) === false || $item['relative'] == true) {
									$link = '/extension/' . $e->handle . '/' . ltrim($item['link'], '/');
								}

								else {
									$link = '/' . ltrim($item['link'], '/');
								}

								// is a navigation group
								if (is_numeric($item['location']) === false) {
									$group_name = $item['location'];
									$group_index = $this->__findLocationIndexFromName($nav, $item['location']);
								}

								// is a legacy numeric index
								else {
									$group_index = $item['location'];
								}

								$child = array(
									'link' =>		$link,
									'name' =>		$item['name'],
									'visible' =>	(
														$item['visible'] == 'no'
															? 'no'
															: 'yes'
													),
									'limit' =>		(
														isset($item['limit'])
															? $item['limit']
															: null
													)
								);

								// add new navigation group
								if ($group_index === false) {
									$nav[] = array(
										'name' =>		$group_name,
										'index' =>		$group_index,
										'children' =>	array($child),
										'limit' =>		(
															isset($item['limit'])
																? $item['limit']
																: null
														)
									);
								}

								// add new location by index
								else {
									$nav[$group_index]['children'][] = $child;
								}

								break;
						}
					}
				}
			}

			####
			# Delegate: ExtensionsAddToNavigation
			# Description: After building the Navigation properties array. This is specifically
			# 			for extentions to add their groups to the navigation or items to groups,
			# 			already in the navigation. Note: THIS IS FOR ADDING ONLY! If you need
			#			to edit existing navigation elements, use the 'NavigationPreRender' delegate.
			# Global: Yes
			Extension::notify(
				'ExtensionsAddToNavigation', '/administration/', array('navigation' => &$nav)
			);

			// Sort navigation groups:
			foreach ($nav as &$group) {
				if (isset($group['children'])) {
					usort($group['children'], function($a, $b) {
						$aOrder = (
							isset($a['order'])
								? $a['order']
								: 0
						);
						$bOrder = (
							isset($b['order'])
								? $b['order']
								: 0
						);

						// Orders are different, use them for sorting:
						if ($aOrder !== $bOrder) {
							return $aOrder - $bOrder;
						}

						// Orders are the same, sort by name:
						return strnatcasecmp($a['name'], $b['name']);
					});
				}
			}

			$pageCallback = Administration::instance()->getPageCallback();

			$pageRoot = $pageCallback['pageroot'] . (isset($pageCallback['context'][0]) ? $pageCallback['context'][0] . '/' : '');
			$found = $this->__findActiveNavigationGroup($nav, $pageRoot);

			## Normal searches failed. Use a regular expression using the page root. This is less
			## efficent and should never really get invoked unless something weird is going on
			if(!$found) $this->__findActiveNavigationGroup($nav, '/^' . str_replace('/', '\/', $pageCallback['pageroot']) . '/i', true);

			ksort($nav);
			$this->_navigation = $nav;

		}

		protected function __findLocationIndexFromName($nav, $name){
			foreach($nav as $index => $group){
				if($group['name'] == $name){
					return $index;
				}
			}

			return false;
		}

		protected function __findActiveNavigationGroup(&$nav, $pageroot, $pattern=false){

			foreach($nav as $index => $contents){
				if(is_array($contents['children']) && !empty($contents['children'])){
					foreach($contents['children'] as $item){

						if($pattern && preg_match($pageroot, $item['link'])){
							$nav[$index]['class'] = 'active';
							return true;
						}

						elseif($item['link'] == $pageroot){
							$nav[$index]['class'] = 'active';
							return true;
						}

					}
				}
			}

			return false;

		}

		public function appendViewOptions(array $options) {
			$div = $this->createElement('div');
			$div->setAttribute('id', 'tab');
			$list = $this->createElement('ul');

			foreach ($options as $name => $link) {
				$item = $this->createElement('li');
				$item->appendChild(
					Widget::Anchor($name, $link, array(
						'class' => (Administration::instance()->getCurrentPageURL() == rtrim($link, '/') ? 'active' : null)
					))
				);

				$list->appendChild($item);
			}

			$div->appendChild($list);
			$this->Form->appendChild($div);
		}

	}

