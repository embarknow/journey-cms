<?php

use Embark\CMS\SystemDateTime;

	class Extension_Event_EmailTemplate implements ExtensionInterface, ExtensionWithIncludesInterface {
		public static $document = null;
		public static $events = array();

		public function about() {
			return (object)array(
				'name'			=> 'Event: Email Template',
				'version'		=> '2.0.0',
				'release-date'	=> '2010-08-04',
				'author'		=> (object)array(
					'name'			=> 'Rowan Lewis, Brendan Abbott',
					'website'		=> 'http://www.randb.com.au/',
					'email'			=> 'me@rowan-lewis.com'
				),
				'type'			=> array(
					'Email', 'Event'
				),
				'provides'		=> array(
					'event_template'
				),
			);
		}

		public function includeFiles() {
			require_once __DIR__ . '/lib/class.event.php';
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function uninstall() {
			return true;
		}

		public function install() {
			return true;
		}

		public function enable() {
			return true;
		}

		public function disable() {
			return true;
		}

		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendTemplatePreRender',
					'callback'	=> 'triggerEmail'
				)
			);
		}

		public function getEventTypes() {
			return array(
				(object)array(
					'class'		=> 'Event_EmailTemplate',
					'name'		=> __('Email Template')
				)
			);
		}

	/*-------------------------------------------------------------------------
		Utility functions:
	-------------------------------------------------------------------------*/

		public function getTemplate($path, $parameters) {
			try {
				if ($path instanceof View) {
					$view = $path;
				}

				else {
					$view = View::loadFromPath($path);
				}

				if (isset($parameters) && !empty($parameters)) {
					Frontend::Parameters()->register($parameters);
				}

				Frontend::Parameters()->{'document-render'} = true;

				return array(
					'content-type'	=> $view->about()->{'content-type'},
					'message'		=> $view->render(Frontend::Parameters())->saveXML()
				);
			}

			catch (ViewException $ex) {
				// oh oh.
				throw $ex;
			}
		}

	/*-------------------------------------------------------------------------
		Delegate functions:
	-------------------------------------------------------------------------*/

		public function triggerEmail($context) {
			$document = $context['document'];
			$xpath = new DOMXPath($document);

			if ($xpath->evaluate('boolean(//parameters/document-render)')) return;

			foreach (self::$events as $event) {
				if ($xpath->evaluate('boolean(' . $event->parameters()->trigger . ')')) {
					/*if(isset($event->parameters()->parameters) && !empty($event->parameters()->parameters)) {
						Frontend::Parameters()->register($event->parameters()->parameters);
					}*/

					self::$document = $document;

					$this->sendEmail($event->parameters());
				}
			}
		}

	/*-------------------------------------------------------------------------*/

		public function sendEmail($template) {
			$xpath = new DOMXPath(self::$document);
			$email = (array)$template;

			//	Remove junk
			unset($email['root-element']);
			unset($email['trigger']);
			unset($email['view']);
			unset($email['parameters']);
			unset($email['pathname']);

			// Replace {xpath} queries:
			foreach ($email as $key => $value) {
				$content = $email[$key];
				$replacements = array();

				// Find queries:
				preg_match_all('/\{[^\}]+\}/', $content, $matches);

				// Find replacements:
				foreach ($matches[0] as $match) {
					$results = @$xpath->query(trim($match, '{}'));

					if ($results->length) {
						$replacements[$match] = $results->item(0)->nodeValue;
					} else {
						$replacements[$match] = '';
					}
				}

				$content = str_replace(
					array_keys($replacements),
					array_values($replacements),
					$content
				);

				$email[$key] = $content;
			}

			// Replace {xpath} queries:
			foreach ($template->parameters as $key => $value) {
				$content = $template->parameters[$key];
				$replacements = array();

				// Find queries:
				preg_match_all('/\{[^\}]+\}/', $content, $matches);

				// Find replacements:
				foreach ($matches[0] as $match) {
					$results = @$xpath->query(trim($match, '{}'));

					if ($results->length) {
						$replacements[$match] = $results->item(0)->nodeValue;
					} else {
						$replacements[$match] = '';
					}
				}

				$content = str_replace(
					array_keys($replacements),
					array_values($replacements),
					$content
				);

				$template->parameters[$key] = $content;
			}

			$view = $this->getTemplate($template->view, $template->parameters);

			//var_dump((string)Frontend::Parameters()->{'id'}); exit;

			// Add values:
			$email['message'] = (string)$view['message'];

			// Determine if we are going to use the SMTP mailer, or the inbuilt Symphony mail function:
			try {
				$smtp = (Extension::status('smtp_email_library') == Extension::STATUS_ENABLED);
			}

			catch (Exception $ex) {
				$smtp = false;
			}

			// Send the email:
			if ($smtp) {
				require_once EXTENSIONS . '/smtp_email_library/lib/class.email.php';

				$libEmail = new LibraryEmail;

				$libEmail->to = $email['recipient-addresses'];
				$libEmail->from = sprintf('%s <%s>', $email['sender-name'], $email['sender-addresses']);
				$libEmail->subject = $email['subject'];
				$libEmail->message = $email['message'];
				$libEmail->setHeader('Reply-To', sprintf('%s <%s>', $email['sender-name'], $email['sender-addresses']));
				$libEmail->setHeader('Content-Type', $view['content-type']);

				try {
					$return = $libEmail->send();
				}

				catch (Exception $e) {
					$return = false;
				}
			}

			//	If SMTP isn't available, or SMTP failed, use the Symphony Mailer
			if (!$smtp || !$return) {
				$return = General::sendEmail(
					$email['recipient-addresses'],  $email['sender-addresses'], $email['sender-name'], $email['subject'], $email['message'], array(
						'content-type'	=> $view['content-type']
					)
				);
			}

			// Log the email:
			$email['method'] = ($smtp ? "smtp" : "symphony");
			$email['success'] = ($return ? 'yes' : 'no');
			$email['date'] = (new SystemDateTime)->format(DateTime::W3C);

			//	TODO: Logging
			return $return;
		}
	}

	return 'Extension_Event_EmailTemplate';

?>