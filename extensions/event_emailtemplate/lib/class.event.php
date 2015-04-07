<?php

use Embark\CMS\Structures\ParameterPool as Context;

	require_once LIB . '/class.entry.php';
	require_once LIB . '/class.event.php';

	class Event_EmailTemplate extends Event {
		public function __construct(){
			// Set Default Values
			$this->_about = new StdClass;
			$this->_parameters = (object)array(
				'root-element' => null,
				'trigger' => null,
				'subject' => null,
				'sender-name' => null,
				'sender-addresses' => null,
				'recipient-addresses' => null,
				'view' => null,
				'parameters' => array(),
			);
		}

		public function getType() {
			return 'Event_EmailTemplate';
		}

		public function getTemplate(){
			return EXTENSIONS . '/event_emailtemplate/templates/template.event.php';
		}

		public function prepareDestinationColumnValue(){
			return Widget::TableData(__('N/A'), array(
				'class' => 'inactive'
			));
		}

	/*-----------------------------------------------------------------------*/

		public function prepare(array $data = null) {
			if (!is_null($data)) {
				$this->about()->name = $data['name'];

				$this->about()->author = (object)[
					"name" =>	Symphony::User()->getFullName(),
					"email" =>	Symphony::User()->email
				];

				$this->parameters()->trigger = $data['trigger'];
				$this->parameters()->subject = $data['subject'];
				$this->parameters()->{'sender-name'} = $data['sender-name'];
				$this->parameters()->{'sender-addresses'} = $data['sender-addresses'];
				$this->parameters()->{'recipient-addresses'} = $data['recipient-addresses'];
				$this->parameters()->view = $data['view'];

				if(isset($data['parameters']) && is_array($data['parameters']) || !empty($data['parameters'])){
					$parameters = array();
					foreach($data['parameters'] as $index => $param){
						$parameters[$param['param']] = $param['value'];
					}

					$this->parameters()->parameters = $parameters;
				}
			}
		}

		public function view(SymphonyDOMElement $wrapper, MessageStack $errors) {
			$page = Administration::instance()->Page;
			$page->insertNodeIntoHead($page->createScriptElement(URL . '/extensions/event_emailtemplate/assets/view.js'));

			$layout = new Layout();
			$column_1 = $layout->createColumn(Layout::SMALL);
			$column_2 = $layout->createColumn(Layout::SMALL);
			$column_3 = $layout->createColumn(Layout::LARGE);

			$fieldset = Widget::Fieldset(__('Essentials'));

			// Name:
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[name]', General::sanitize($this->about()->name)));

			if (isset($errors->{'about::name'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'about::name'});
			}

			$fieldset->appendChild($label);

			// Expression:
			$label = Widget::Label(__('Trigger'));
			$label->appendChild(Widget::Textarea(
				'fields[trigger]', General::sanitize($this->parameters()->{'trigger'}),
				array(
					'rows'	=> 3
				)
			));

			if (isset($errors->{'trigger'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'trigger'});
			}

			$fieldset->appendChild($label);

			$help = $page->createElement('p');
			$help->addClass('help');
			$help->setValue(__('Enter an XPath expression to trigger sending this email.'));
			$fieldset->appendChild($help);

			$column_1->appendChild($fieldset);

			$fieldset = Widget::Fieldset(__('Meta Data'));

			// Subject:
			$label = Widget::Label(__('Subject'));
			$label->appendChild(Widget::Input(
				'fields[subject]', General::sanitize($this->parameters()->{'subject'})
			));

			if (isset($errors->{'subject'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'subject'});
			}

			$fieldset->appendChild($label);

			// Sender Name:
			$label = Widget::Label(__('Sender Name'));
			$label->appendChild(Widget::Input(
				'fields[sender-name]', General::sanitize($this->parameters()->{'sender-name'})
			));

			if (isset($errors->{'sender-name'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'sender-name'});
			}

			$fieldset->appendChild($label);

			// Sender Address(es):
			$label = Widget::Label(__('Sender Address(es)'));
			$label->appendChild(Widget::Input(
				'fields[sender-addresses]', General::sanitize($this->parameters()->{'sender-addresses'})
			));

			if (isset($errors->{'sender-addresses'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'sender-addresses'});
			}

			$fieldset->appendChild($label);

			// Recipient Address(es):
			$label = Widget::Label(__('Recipient Address(es)'));
			$label->appendChild(Widget::Input(
				'fields[recipient-addresses]', General::sanitize($this->parameters()->{'recipient-addresses'})
			));

			if (isset($errors->{'recipient-addresses'})) {
				$label = Widget::wrapFormElementWithError($label, $errors->{'recipient-addresses'});
			}

			$fieldset->appendChild($label);

			$help = $page->createElement('p');
			$help->addClass('help');
			$text = $page->createDocumentFragment();
			$text->appendXML(__('To access the current document, use XPath expressions: <code>{datasource/entry/...}</code>'));
			$help->appendChild($text);
			$fieldset->appendChild($help);

			$column_2->appendChild($fieldset);

			$fieldset = Widget::Fieldset(__('Template'));

			// View:
			$label = Widget::Label(__('View'));
			$options = array();

			foreach (new ViewIterator() as $view) {
				$options[] = array(
					$view->path,
					($view->path == $this->parameters()->{'view'}),
					$view->path
				);
			}

			$select = Widget::Select('fields[view]', $options);
			$select->setAttribute('id', 'context');

			$label->appendChild($select);
			$fieldset->appendChild($label);

			// URL Parameters:
			foreach(new ViewIterator as $view) {

				if(!isset($view->about()->{'url-parameters'})) continue;

				$this->appendDuplicator(
					$fieldset, $view,
					($this->parameters()->view == $view->path) ? $this->parameters()->parameters : null
				);
			}

			$column_3->appendChild($fieldset);

			$layout->appendTo($wrapper);
		}

		protected function appendDuplicator(SymphonyDOMElement $wrapper, View $view, array $items = null) {
			$document = $wrapper->ownerDocument;

			$duplicator = new Duplicator(__('Add Item'));
			$duplicator->addClass('parameter-duplicator context context-' . str_replace("/","_", $view->path));

			$item = $duplicator->createTemplate(__('Parameter'));
			$label = Widget::Label(__('Name'));
			$options = array();

			if(isset($view->about()->{'url-parameters'}) && !empty($view->about()->{'url-parameters'})) {
				foreach($view->about()->{'url-parameters'} as $index => $p) {
					$options[] = array(
						$p, false, $p
					);
				}
			}

			$label->appendChild(Widget::Select('param', $options));
			$item->appendChild($label);

			$label = Widget::Label(__('Value'));
			$label->appendChild(Widget::Textarea(
				'value', null,
				array(
					'rows'	=> 2
				)
			));
			$item->appendChild($label);

			$help = $document->createElement('p');
			$help->addClass('help');
			$help->setValue(__('Enter an XPath expression to set the value of this parameter.'));
			$item->appendChild($help);

			if(is_array($items)){
				foreach($items as $param => $value) {
					$item = $duplicator->createInstance(__('Parameter'));
					$label = Widget::Label(__('Parameter'));

					$options = array();
					if(isset($view->about()->{'url-parameters'}) && !empty($view->about()->{'url-parameters'})) {
						foreach($view->about()->{'url-parameters'} as $p) {
							$options[] = array(
								$p, $param == $p, $p
							);
						}
					}

					$label->appendChild(Widget::Select('param', $options));
					$item->appendChild($label);

					$label = Widget::Label(__('Value'));
					$label->appendChild(Widget::Textarea(
						'value', General::sanitize($value),
						array(
							'rows'	=> 2
						)
					));
					$item->appendChild($label);
				}
			}

			$duplicator->appendTo($wrapper);
		}

	/*-----------------------------------------------------------------------*/

		/*
		**	Email Event always triggers, it's up to the Delegate in the
		**	extension driver to determine whether it runs though
		*/
		public function canTrigger(array $data)
		{
			return true;
		}

		public function trigger(Context $ParameterOutput, array $postdata)
		{
			$result = new XMLDocument;
			$result->appendChild($result->createElement($this->parameters()->{'root-element'}));
			$root = $result->documentElement;
			$root->setAttribute('sent', 'no');

			Extension::load('event_emailtemplate');
			Extension_Event_EmailTemplate::$events[] = $this;

			return $result;
		}
	}
