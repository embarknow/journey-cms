<?php

namespace Embark\Journey\Exceptions;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Phly\Http\Stream;
use Embark\Journey\Exceptions\XMLException;
use Embark\Journey\Markup\XMLDocument;
use Embark\Journey\Markup\XMLElement;

class ErrorHandler
{
    /**
     * Template path
     *
     * @var string
     */
    protected $templatePath;

    /**
     * Exception handler for native PHP exceptions
     * @var ExceptionHandlerInterface
     */
    protected $nativeExceptionHandler;

    /**
     * Exception handler for XML exceptions
     * @var ExceptionHandlerInterface
     */
    protected $xmlExceptionHandler;

    /**
     * Constructor accepts a path to look for xsl templates
     *
     * @param string $templatePath
     *  the path to look for xsl templates in
     * @param ExceptionHandlerInterface $nativeExceptionHandler
     *  a handler for native exceptions
     * @param ExceptionHandlerInterface $xmlExceptionHandler
     *  a handler for xml exceptions
     */
    public function __construct($templatePath, ExceptionHandlerInterface $nativeExceptionHandler, ExceptionHandlerInterface $xmlExceptionHandler)
    {
        $this->templatePath = $templatePath;
        $this->nativeExceptionHandler = $nativeExceptionHandler;
        $this->xmlExceptionHandler = $xmlExceptionHandler;
    }

    /**
     * Invokable handler to accept any type of Exception and act accordingly
     *
     * @param  RequestInterface  $request
     *  the HTTP request object
     * @param  ResponseInterface $response
     *  the current HTTP response object
     * @param  Exception         $e
     *  an Exception object which can be an XMLException or a standard PHP Exception derivitive
     *
     * @return ResponseInterface
     *  a modified HTTP response object
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, Exception $e)
    {
        $output = 'Application Error or some shit.'; // Temporary shit

        // Choose how to process the exception
        // Native exceptions need a little more processing
        if ($e->getMessage() instanceof XMLElement) {
            $handler = $this->xmlExceptionHandler;
        } else {
            $handler = $this->nativeExceptionHandler;
        }

        $data = $handler($e);

        $exceptionType = get_class($e);
        $template = $this->getTemplate($exceptionType);

        // Transform the data here and add to the output

        $response = $response->withStatus(500)
                             ->withHeader('Content-type', 'text/html')
                             ->withBody(new Stream('php://temp', 'r+'))
                             ->write($output);

        return $response;
    }

    /**
     * Get an xsl template instance from the stored template path
     *
     * @param  string $template
     *  name of the template file
     *
     * @return XMLDocument
     *  a loaded instance of the template file
     */
    protected function getTemplate($template)
    {
        $file = rtrim($this->templatePath, '/') . '/' . strtolower($template) . '.xsl';
        $fallback = rtrim($this->templatePath, '/') . '/exception.xsl';
        $document = new XMLDocument();

        if (file_exists($file)) {
            $document->load($file);
        } else {
            $document->load($fallback);
        }

        return $document;
    }
}
