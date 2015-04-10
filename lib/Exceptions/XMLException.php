<?php

namespace Embark\Journey\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Embark\Journey\Markup\XMLElement;

/**
 * Exception
 */
class XMLException extends Exception
{
    /**
     * An xml exception message
     *
     * @var XMLElement
     */
    protected $message;

    /**
     * An HTTP response object
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Create a new exception with a response object
     *
     * @param XMLElement $message
     *  an exception message as xml
     * @param ResponseInterface $response
     *  an HTTP response object
     */
    public function __construct(XMLElement $message, ResponseInterface $response)
    {
        parent::__construct();

        $this->message = $message;
        $this->response = $response;
    }

    /**
     * Get the response object
     *
     * @return ResponseInterface
     *  the stored HTTP response object
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the exception message
     * @return XMLElement
     *  the xml exception message
     */
    public function getMessage()
    {
        return $this->message;
    }
}
