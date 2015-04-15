<?php

final class MessageStack implements Iterator
{
    private $messages = array();

    public function __construct(array $messages = null)
    {
        $this->messages = array();

        if (!is_null($messages)) {
            $this->messages = $messages;
        }
    }

    public function rewind()
    {
        reset($this->messages);
    }

    public function current()
    {
        return current($this->messages);
    }

    public function key()
    {
        return key($this->messages);
    }

    public function next()
    {
        return next($this->messages);
    }

    public function valid()
    {
        return ($this->current() !== false);
    }

    public function length()
    {
        return count($this->messages);
    }

    ## TODO: This is a bit voodoo. Maybe come up with a better solution
    private static function __sanitiseIdentifier($identifier)
    {
        return str_replace('_', '-', $identifier);
    }

    public function append($identifier, $message)
    {
        if ($identifier == null) {
            $identifier = count($this->messages);
        }
        $this->messages[self::__sanitiseIdentifier($identifier)] = $message;

        return $identifier;
    }

    public function remove($identifier)
    {
        $element = self::__sanitiseIdentifier($identifier);

        if (isset($this->messages[$identifier])) {
            unset($this->messages[$identifier]);
        }
    }

    public function flush()
    {
        $this->messages = array();
    }

    public function __get($identifier)
    {
        return (isset($this->messages[$identifier]) ? $this->messages[$identifier] : null);
    }

    public function __isset($identifier)
    {
        return isset($this->messages[$identifier]);
    }

    public function appendTo(SymphonyDOMElement $wrapper)
    {
        $document = $wrapper->ownerDocument;
        $list = $document->createElement('ol');
        $list->setAttribute('class', 'error-list');

        foreach ($this as $key => $message) {
            if (!is_numeric($key)) {
                $key = $key . ': ';
            } else {
                $key = '';
            }

            if ($message instanceof MessageStack) {
                $item = $document->createElement('li', $key);

                $message->appendTo($item);
            } elseif (is_array($message)) {
                $item = $document->createElement('li', $key . array_shift($message));
            } elseif ($message instanceof STDClass) {
                $message = (array)$message;
                $item = $document->createElement('li', $key . array_shift($message));
            } elseif ($message instanceof DOMDocumentFragment) {
                $fragment = Administration::instance()->Page->createDocumentFragment();
                $fragment->appendChild(new DOMText());

                $item = $document->createElement('li');
                $item->appendChild($fragment);
            } else {
                $item = $document->createElement('li', $key . $message);
            }

            $list->appendChild($item);
        }

        $wrapper->appendChild($list);
    }
}
