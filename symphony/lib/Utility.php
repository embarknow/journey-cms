<?php

use Exception;
use General;
use Symphony;
use MessageStack;

use Embark\CMS\Configuration\Controller as Configuration;

final class Utility
{
    private $_properties;

    const ERROR_XSLT_INVALID = 10;
    const ERROR_NAME_INVALID = 20;
    const ERROR_CANNOT_READ = 30;
    const ERROR_CANNOT_WRITE = 40;

    public function __construct($name=null, $body=null)
    {
        $this->_properties = array();

        if (!is_null($name)) {
            $this->name = $name;
        }
        if (!is_null($body)) {
            $this->body = $body;
        }
    }

    public static function save(Utility $utility)
    {
        $file = UTILITIES . "/{$utility->name}";

        General::writeFile(
            $file,
            $utility->body,
            Symphony::Configuration()['system']['file-write-mode']
        );

        return file_exists($file);
    }

    public static function delete($name)
    {
        unlink(UTILITIES . "/{$name}");
    }

    public static function load($file)
    {
        if (!file_exists($file) && is_readable($file)) {
            throw new Exception("Utility '{$file}' does not exist or is not readable.", self::ERROR_CANNOT_READ);
        }
        return new self(basename($file), file_get_contents($file));
    }

    public function findTemplates()
    {
        $templates = array();

        preg_match_all('/xsl:template\s+name="([^"]+)"/i', $this->body, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            if (empty($m)) {
                continue;
            }

            $templates[] = $m[1];
        }

        return $templates;
    }

    public function findIncludes()
    {
        $includes = array();

        preg_match_all('/xsl:(include|import)\s+href="([^"]+)"/i', $this->body, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            if (empty($m)) {
                continue;
            }

            $includes[] = basename($m[2]);
        }

        return $includes;
    }

    public function validate(MessageStack $messages = null, $validateAsNew = true)
    {
        $valid = true;

        if ($this->name == '.xsl' || strlen(trim($this->name)) == 0) {
            if ($messages instanceof MessageStack) {
                $messages->append('name', 'This is a required field.');
            }

            $valid = false;
        } elseif ($validateAsNew && file_exists(UTILITIES . "/{$this->name}")) {
            if ($messages instanceof MessageStack) {
                $messages->append('name', 'A utility with name name already exists.');
            }

            $valid = false;
        }

        $error = array();
        if (strlen(trim($this->body)) == 0) {
            if ($messages instanceof MessageStack) {
                $messages->append('body', 'This is a required field.');
            }

            $valid = false;
        } elseif (!General::validateXML($this->body, $error)) {
            if ($messages instanceof MessageStack) {
                $messages->append('body', sprintf('XSLT specified is invalid. The following error was returned: "%s near line %s"', $error[0]->message, $error[0]->line));
            }

            $valid = false;
        }

        return $valid;
    }

    public function __get($name)
    {
        if ($name == 'properties') {
            return $this->_properties;
        } elseif ($name == 'directory') {
            return $this->_directory;
        }
        return $this->_properties[$name];
    }

    public function __set($name, $value)
    {
        if ($name == 'properties') {
            $this->_properties = $value;
        } elseif ($name == 'directory') {
            $this->_directory = $value;
        } else {
            $this->_properties[$name] = $value;
        }
    }
}
