<?php

	class GenericExceptionHandler {
		public static $enabled;
		protected static $log;

		public static function initialise(Log $log = null) {
			self::$enabled = true;

			if ($log instanceof Log) {
				self::$log = $log;
			}

			set_exception_handler(array(__CLASS__, 'handler'));
		}

		protected static function __nearbyLines($line, $file, $isString = false, $window = 5, $normalise_tabs = true) {
			if ($isString === false && !is_null($file)) {
				$file = file($file);
			}

			else {
				$file = preg_split('/[\r\n]+/', $file);
			}

			$result = array_slice($file, max(0, ($line - 1) - $window), $window * 2, true);

			if ($normalise_tabs == true && !empty($result)) {
				$length = NULL;

				foreach ($result as $string) {
					preg_match('/^\t+/', $string, $match);

					if (
						strlen(trim($string)) > 0
						&& (is_null($length) || strlen($match[0]) < $length)
					) {
						$length = strlen($match[0]);
					}
				}

				if (is_null($length) === false && $length > 0) {
					foreach ($result as $index => $string) {
						$result[$index] = preg_replace('/^\t{'.$length.'}/', NULL, $string);
					}
				}
			}

			return $result;
		}

		public static function handler($e, $exit = true) {
			if (self::$enabled !== true) return;

			try {
				$class = __CLASS__;
				$exception_type = get_class($e);

				// Log the exception:
				if (self::$log instanceof Log) {
					self::$log->pushExceptionToLog($e);
				}

				// Find a handler for the exception:
				if (
					class_exists("{$exception_type}Handler")
					&& method_exists("{$exception_type}Handler", 'render')
				) {
					$class = "{$exception_type}Handler";
				}

				$output = call_user_func(array($class, 'render'), $e);

				if (headers_sent() === false) {
					header('HTTP/1.0 500 Internal Server Error');
					header('Content-Type: text/html; charset=utf-8');
					header(sprintf('Content-Length: %d', strlen($output)));
				}

				echo $output;
			}

			catch (Exception $e) {
				echo "<h1>An error occurred while attempting to handle an exception. See below for more details.</h1>";

				ob_start();
				print_r($e);
				$data = ob_get_clean();

				echo '<pre>', htmlentities($data);
			}

			if ($exit) exit;
		}

		public static function render($e) {
			$xml = new DOMDocument('1.0', 'utf-8');
			$xml->formatOutput = true;

			$root = $xml->createElement('data');
			$xml->appendChild($root);

			$details = $xml->createElement('details', $e->getMessage());
			$details->setAttribute('type', General::sanitize(
				$e instanceof ErrorException
					? GenericErrorHandler::$errorTypeStrings[$e->getSeverity()]
					: 'Fatal Error'
			));
			$details->setAttribute('file', General::sanitize($e->getFile()));
			$details->setAttribute('line', $e->getLine());
			$root->appendChild($details);

			// Nearby lines:
			$nearby_lines = self::__nearByLines($e->getLine(), $e->getFile());

			$lines = $xml->createElement('nearby-lines');

			$markdown = "\t" . $e->getMessage() . "\n";
			$markdown .= "\t" . $e->getFile() . " line " . $e->getLine() . "\n\n";

			foreach ($nearby_lines as $line_number => $string) {
				$markdown .= "\t{$string}";

				$string = trim(str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', General::sanitize($string)));
				$item = $xml->createElement('item', (strlen($string) == 0 ? '&nbsp;' : $string));
				$item->setAttribute('number', $line_number + 1);
				$lines->appendChild($item);

			}

			$root->appendChild($lines);

			$element = $xml->createElement('markdown');
			$element->appendChild($xml->createCDATASection($markdown));
			$root->appendChild($element);

			// Backtrace:
			$trace = $xml->createElement('backtrace');

			foreach ($e->getTrace() as $t) {
				$item = $xml->createElement('item');

				if (isset($t['file'])) {
					$item->setAttribute('file', General::sanitize($t['file']));
				}

				if (isset($t['line'])) {
					$item->setAttribute('line', $t['line']);
				}

				if (isset($t['class'])) {
					$item->setAttribute('class', General::sanitize($t['class']));
				}

				if (isset($t['type'])) {
					$item->setAttribute('type', $t['type']);
				}

				$item->setAttribute('function', General::sanitize($t['function']));
				$trace->appendChild($item);
			}

			$root->appendChild($trace);

			if (class_exists('Frontend') && Frontend::Parameters() instanceof Register) {
				$params = Frontend::Parameters();

				$parameters = $xml->createElement('parameters');

				foreach ($params as $key => $parameter) {
					$p = $xml->createElement('param');
					$p->setAttribute('key', $key);
					$p->setAttribute('value', (string)$parameter);

					if (is_array($parameter->value) && count($parameter->value) > 1) {
						foreach ($parameter->value as $v) {
							$p->appendChild(
								$xml->createElement('item', (string)$v)
							);
						}
					}

					$parameters->appendChild($p);
				}

				$root->appendChild($parameters);
			}

			return self::__transform($xml);
		}

		protected static function __transform(DOMDocument $xml, $template='exception.generic.xsl'){
			$path = TEMPLATES . '/'. $template;

			if (file_exists(MANIFEST . '/templates/' . $template)) {
				$path = MANIFEST . '/templates/' . $template;
			}

			return XSLProc::transform($xml, file_get_contents($path), XSLProc::XML, array('root' => URL));
		}
	}

	class GenericErrorHandler {
		public static $enabled;
		protected static $log;

		public static $errorTypeStrings = array(
			E_NOTICE =>         		'Notice',
			E_WARNING =>        		'Warning',
			E_ERROR =>          		'Error',
			E_PARSE =>          		'Parsing Error',

			E_CORE_ERROR =>     		'Core Error',
			E_CORE_WARNING =>   		'Core Warning',
			E_COMPILE_ERROR =>  		'Compile Error',
			E_COMPILE_WARNING => 		'Compile Warning',

			E_USER_NOTICE =>    		'User Notice',
			E_USER_WARNING =>   		'User Warning',
			E_USER_ERROR =>     		'User Error',

			E_STRICT =>         		'Strict Notice',
			E_RECOVERABLE_ERROR =>  	'Recoverable Error'
		);

		public static function initialise(Log $log = null) {
			self::$enabled = true;

			if ($log instanceof Log) {
				self::$log = $log;
			}

			set_error_handler(array(__CLASS__, 'handler'), error_reporting());
		}

		public static function isEnabled(){
			return (bool)error_reporting() AND self::$enabled;
		}

		public static function handler($code, $message, $file = null, $line = null) {
			throw new ErrorException($message, 0, $code, $file, $line);
		}
	}