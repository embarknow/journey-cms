<?php

/**
 * Dictionary Class
 *
 * Contains the dictionary for the current language and provides the translate function
 */
class Dictionary
{
    private $_strings;

    public function __construct(array $strings)
    {
        $this->_strings = $strings;
    }

    public function translate($string, array $tokens = null)
    {
        $translated = $this->find($string);

        if ($translated === false) {
            $translated = $string;
        }

        if (!is_null($tokens) && is_array($tokens) && !empty($tokens)) {
            $translated = vsprintf($translated, $tokens);
        }

        return $translated;
    }

    public function find($string)
    {
        if (isset($this->_strings[$string])) {
            return $this->_strings[$string];
        }

        return false;
    }

    public function add($from, $to)
    {
        $this->_strings[$from] = $to;
    }

    public function merge($strings)
    {
        if (is_array($strings)) {
            $this->_strings = array_merge($this->_strings, $strings);
        }
    }

    public function remove($string)
    {
        unset($this->_strings[$string]);
    }
}
