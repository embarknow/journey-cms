<?php

namespace Embark\CMS\Configuration;

use ArrayAccess;
use DOMDocument;
use DOMNode;
use DOMText;
use Exception;
use Mutex;
use SimpleXMLElement;
use StdClass;

class Element implements ArrayAccess
{
    protected $doc;
    protected $path;
    protected $properties;

    public function __construct($path)
    {
        $this->properties = new StdClass;
        $this->path = $path;

        if (!file_exists($path)) {
            $this->doc = new SimpleXMLElement('<configuration></configuration>');
        } else {
            try {
                $this->doc = simplexml_load_file($this->path);
                self::loadVariablesFromNode($this->doc, $this->properties);
            } catch (Exception $e) {
                throw new Exception(sprintf(
                    "Error while reading '%s': %s",
                    $this->path, $e->getMessage()
                ));
            }
        }
    }

    protected function loadVariablesFromNode(SimpleXMLElement $elements, &$group)
    {
        // Determine the type of group being created. Either an array or stdclass object
        $group = isset($elements->item)
            ? array()
            : new StdClass;

        foreach ($elements as $element) {
            $name = $element->getName();

            // If the name is 'item' use a numeric index
            $index = ($name == 'item')
                ? count($group)
                : $name;

            if (count($element->children()) > 0) {
                $value = null;
                $this->loadVariablesFromNode($element, $value);
            } else {
                $value = (string)$element;
            }

            // Using the value above, construct the group
            if (is_array($group)) {
                $group[$index] = $value;
            } else {
                $group->$name = $value;
            }
        }
    }

    public function properties()
    {
        return $this->properties;
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    public function offsetExists($name)
    {
        return isset($this->properties->$name);
    }

    public function __get($name) {
        return $this->offsetGet($name);
    }

    public function offsetGet($name)
    {
        if (isset($this->properties->$name) === false) {
            return null;
        }

        return $this->properties->$name;
    }

    public function __set($name, $value)
    {
        return $this->offsetSet($name, $value);
    }

    public function offsetSet($name, $value)
    {
        return $this->properties->$name = $value;
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    public function offsetUnset($name)
    {
        unset($this->properties->$name);
    }

    public function save($path = null)
    {
        try {
            $doc = new DOMDocument('1.0', 'UTF-8');
            $doc->formatOutput = true;
            $path = (
                isset($path)
                    ? $path
                    : $this->path
            );

            $root = $doc->createElement('configuration');
            $doc->appendChild($root);

            $this->generateXML($this->properties, $root);

            // Wait for a few seconds if file is locked:
            while (!Mutex::acquire($path, 2)) {
                usleep(500000);
            }

            file_put_contents($path, $doc->saveXML());

            Mutex::release($path);
        } catch (Exception $e) {
            throw new Exception(sprintf(
                "Error while writing '%s': %s",
                $this->path, $e->getMessage()
            ));
        }
    }

    protected function generateXML($elements, DOMNode &$parent)
    {
        foreach ($elements as $name => $e) {
            $element_name = (
                is_numeric($name)
                    ? 'item'
                    : $name
            );

            if ($e instanceof StdClass || is_array($e)) {
                $element = $parent->ownerDocument->createElement($element_name);

                $this->generateXML($e, $element);
            } else {
                $element = $parent->ownerDocument->createElement($element_name);
                $element->appendChild(new DOMText((string)$e));
            }

            $parent->appendChild($element);
        }
    }
}

