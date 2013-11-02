<?php

	require_once(LIB . '/class.messagestack.php');

	class XSLProcException extends Exception {
		private $error;

		public function getType() {
			return $this->error->type;
		}

		public function __construct($message) {
			parent::__construct($message);

			$this->error = NULL;
			$bFoundFile = false;

			if (XSLProc::getErrors() instanceof MessageStack) {
				foreach (XSLProc::getErrors() as $e) {
					if ($e->type == XSLProc::ERROR_XML) {
						$this->error = (object)array();
						$this->error->file = XSLProc::lastXML();
						$this->error->line = $e->line;
						$bFoundFile = true;

						break;
					}

					else if (strlen(trim($e->file)) == 0) {
						continue;
					}

					$this->error = (object)array();
					$this->error->file = $e->file;
					$this->error->line = $e->line;
					$bFoundFile = true;

					break;
				}

				if (is_null($this->error)) {
					foreach(XSLProc::getErrors() as $e){
						if (preg_match_all('/(\/?[^\/\s]+\/.+.xsl) line (\d+)/i', $e->message, $matches, PREG_SET_ORDER)) {
							$this->file = $matches[0][1];
							$this->line = $matches[0][2];
							$bFoundFile = true;

							break;
						}

						else if (preg_match_all('/([^:]+): (.+) line (\d+)/i', $e->message, $matches, PREG_SET_ORDER)) {
							//throw new Exception("Fix XSLPROC Frontend doesn't have access to Page");

							$this->line = $matches[0][3];
							$this->file = Frontend::instance()->loadedView()->pathname;
							$bFoundFile = true;
						}
					}
				}
			}
		}
	}

	Class XSLProcExceptionHandler extends GenericExceptionHandler{

		public static function render($e){

			$xml = new DOMDocument('1.0', 'utf-8');
			$xml->formatOutput = true;

			$root = $xml->createElement('data');
			$xml->appendChild($root);

			$details = $xml->createElement('details', $e->getMessage());
			$details->setAttribute('type', ($e->getType() == XSLProc::ERROR_XML ? 'XML' : $e->getFile()));
			$details->setAttribute('file', General::sanitize($e->getFile()));
			$details->setAttribute('line', $e->getLine());
			$root->appendChild($details);

			$nearby_lines = self::__nearByLines($e->getLine(), $e->getFile(), $e->getType() == XSLProc::ERROR_XML, 6);

			$lines = $xml->createElement('nearby-lines');

			$markdown = "\t" . $e->getMessage() . "\n";
			$markdown .= "\t" . $e->getFile() . " line " . $e->getLine() . "\n\n";

			foreach($nearby_lines as $line_number => $string){

				$markdown .= "\t{$string}";

				$string = trim(str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', General::sanitize($string)));
				$item = $xml->createElement('item');
				$item->setAttribute('number', $line_number + 1);
				$cdata = $xml->createCDATASection(strlen($string) == 0 ? '&nbsp;' : $string);
				$item->appendChild($cdata);
				$lines->appendChild($item);
			}
			$root->appendChild($lines);

			$element = $xml->createElement('markdown'); //, General::sanitize($markdown)));
			$element->appendChild($xml->createCDATASection($markdown));
			$root->appendChild($element);

			$processing_errors = $xml->createElement('processing-errors');

			if(XSLProc::getErrors() instanceof MessageStack){
				foreach(XSLProc::getErrors() as $error){
					$error->file = str_replace(WORKSPACE . '/', NULL, $error->file);
					$item = $xml->createElement('item', trim(General::sanitize($error->message)));
					if(strlen(trim($error->file)) == 0) $item->setAttribute('file', General::sanitize($error->file));
					if(strlen(trim($error->line)) == 0) $item->setAttribute('line', $error->line);
					$processing_errors->appendChild($item);
				}
			}

			$root->appendChild($processing_errors);

			if(Frontend::Parameters() instanceof Register) {
				$params = Frontend::Parameters();

				$parameters = $xml->createElement('parameters');

				foreach($params as $key => $parameter){
					$p = $xml->createElement('param');
					$p->setAttribute('key', $key);
					$p->setAttribute('value', (string)$parameter);

					if(is_array($parameter->value) && count($parameter->value) > 1){
						foreach($parameter->value as $v){
							$p->appendChild(
								$xml->createElement('item', (string)$v)
							);
						}
					}

					$parameters->appendChild($p);
				}

				$root->appendChild($parameters);
			}


			return parent::__transform($xml, 'exception.xslt.xsl');
		}
	}

	Final Class XSLProc{

		const ERROR_XML = 1;
		const ERROR_XSL = 2;

		const DOC = 3;
		const XML = 4;

		static private $errors;

		static private $lastXML;
		static private $lastXSL;

		public static function lastXML(){
			return self::$lastXML;
		}

		public static function lastXSL(){
			return self::$lastXSL;
		}

		public static function isXSLTProcessorAvailable(){
			return (class_exists('XSLTProcessor'));
		}

		static private function processLibXMLerrors($type=self::ERROR_XML){
			if (!(self::$errors instanceof MessageStack)) {
				self::$errors = new MessageStack;
			}

			foreach (libxml_get_errors() as $error) {
				$error->type = $type;
				self::$errors->append(NULL, $error);
			}

			libxml_clear_errors();
		}

		public static function tidyDocument(DOMDocument $xml){

			$result = XSLProc::transform($xml,
				'<?xml version="1.0" encoding="UTF-8"?>
				<xsl:stylesheet version="1.0"
				  xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

				<xsl:output method="xml" indent="yes" />

				<xsl:strip-space elements="*"/>

				<xsl:template match="node() | @*">
					<xsl:copy>
						<xsl:apply-templates select="node() | @*"/>
					</xsl:copy>
				</xsl:template>

				</xsl:stylesheet>', XSLProc::DOC);

			$result->preserveWhitespace = true;
			$result->formatOutput = true;

			return $result;

		}

		public static function flush(){
			if(!(self::$errors instanceof MessageStack)){
				self::$errors = new MessageStack;
			}

			self::$errors->flush();
			self::$lastXML = self::$lastXSL = NULL;
		}

		static public function transform($xml, $xsl, $output=self::XML, array $parameters=array(), array $register_functions=array()){

			self::flush();

			self::$lastXML = $xml;
			self::$lastXSL = $xsl;
			$result = null;

			libxml_use_internal_errors(true);
			libxml_clear_errors();

			if($xml instanceof DOMDocument){
				$XMLDoc = $xml;
			}
			else{
				$XMLDoc = new DOMDocument;
				$XMLDoc->loadXML($xml);
			}

			self::processLibXMLerrors(self::ERROR_XML);

			if($xsl instanceof DOMDocument){
				$XSLDoc = $xsl;
			}
			else{
				$XSLDoc = new DOMDocument;
				$XSLDoc->loadXML($xsl);
			}

			if(!self::hasErrors() && ($XSLDoc instanceof DOMDocument) && ($XMLDoc instanceof DOMDocument)){
				$XSLProc = new XSLTProcessor;
				$XSLProc->registerPHPFunctions();
				$XSLProc->importStyleSheet($XSLDoc);

				if(is_array($parameters) && !empty($parameters)) $XSLProc->setParameter('', $parameters);

				self::processLibXMLerrors(self::ERROR_XSL);

				if(!self::hasErrors()){
					$result = $XSLProc->{'transformTo'.($output==self::XML ? 'XML' : 'Doc')}($XMLDoc);
					self::processLibXMLerrors(self::ERROR_XML);
				}
			}

			if (is_null($result) && self::hasErrors() && !isset($_GET['profiler'])) {
				throw new XSLProcException('Transformation Failed');
			}

			return $result;
		}

		static public function hasErrors(){
			return (bool)(self::$errors instanceof MessageStack && self::$errors->valid());
		}

		static public function getErrors(){
			return self::$errors;
		}
	}