<?php

require_once('class.xmldocument.php');
require_once('class.documentheaders.php');

class HTMLDocument extends XMLDocument
{
    public $Html;
    public $Head;
    public $Body;
    public $Headers;

    public function createScriptElement($path)
    {
        $element = $this->createElement('script');
        $element->setAttribute('type', 'text/javascript');
        $element->setAttribute('src', $path);

        // Creating an empty text node forces <script></script>
        $element->appendChild($this->createTextNode(''));

        return $element;
    }

    public function createStylesheetElement($path, $type = 'screen')
    {
        $element = $this->createElement('link');
        $element->setAttribute('type', 'text/css');
        $element->setAttribute('rel', 'stylesheet');
        $element->setAttribute('media', $type);
        $element->setAttribute('href', $path);
        return $element;
    }

    public function setDTD($value)
    {
        $this->dtd = $value;
    }

    public function __construct($version = '1.0', $encoding = 'utf-8', $dtd = 'html')
    {
        parent::__construct($version, $encoding);
        $this->registerNodeClass('DOMDocument', 'HTMLDocument');
        $this->registerNodeClass('DOMElement', 'SymphonyDOMElement');

        $this->appendChild($this->createElement('html'));
        /*$this->loadHTML(
            sprintf("%s\n<html/>", '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'
        ));
        */
        $this->Headers = new DocumentHeaders(array(
            'Content-Type', "text/html; charset={$encoding}",
        ));

        $this->dtd = $dtd;

        //if(is_null($dtd)){
        //    $dtd = DOMImplementation::createDocumentType('html');
        //}

        //$this->Document = DOMImplementation::createDocument(NULL, 'html', $dtd);
        //$this->version = $version;
        //$this->encoding = $encoding;

        $this->preserveWhitespace = false;
        $this->formatOutput = true;

        $this->Html = $this->documentElement;

        $this->Head = $this->createElement('head');
        $this->Html->appendChild($this->Head);

        $this->Body = $this->createElement('body');
        $this->Html->appendChild($this->Body);
    }

    public function insertNodeIntoHead(DOMElement $element, $position = null)
    {
        if (is_null($position)) {
            $this->Head->appendChild($element);
            return;
        }

        $node = $this->xpath("/html/head/*[position() >= {$position}]")->item(0);

        if (is_null($node)) {
            $this->Head->appendChild($element);
        } else {
            $node->parentNode->insertBefore($element, $node);
        }
    }

    public function isElementInHead($element, $attr = null, $nodeValue = null)
    {
        $xpath = "/html/head/{$element}";
        if (!is_null($attr)) {
            $xpath .= "/@{$attr}[contains(.,'{$nodeValue}')]";
        }

        $nodes = $this->xpath($xpath);
        return ($nodes->length > 0 ? true : false);
    }

    public function __toString()
    {
        return sprintf("<!DOCTYPE %s>\n%s", $this->dtd, $this->saveHTML());
    }
}


/*
// USAGE EXAMPLE:

$page = new HTMLDocument('1.0', 'utf-8', 'html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"');

$page->Head->appendChild(
    $page->createElement('title', 'A New Page')
);

$page->Html->setAttribute('lang', 'en');

$Form = $page->createElement('form');
$page->Body->appendChild($Form);

$Form->setAttribute('action', 'blah.php');
$Form->setAttribute('method', 'POST');

$page->insertNodeIntoHead(
    $page->createStylesheetElement('./blah/styles.css', 'print')
);

$page->insertNodeIntoHead(
    $page->createScriptElement('./blah/scripts.js'), 2
);

if($page->isElementInHead('script', 'src', 'scripts.js') == false){
    $page->insertNodeIntoHead(
        $page->createScriptElement('./blah/scripts.js'), 2
    );
}

//Uncomment this to see output as plain text
//$page->Headers->append('Content-Type', 'text/plain');

$output = (string)$page;
$page->Headers->append('Content-Length', strlen($output));

$page->Headers->render();
echo $output;
exit();

*/
