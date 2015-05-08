<?php

use Exception;
use MessageStack;
use XSLTProc;

class XSLProcException extends Exception
{
    private $error;

    public function getType()
    {
        return $this->error->type;
    }

    public function __construct($message)
    {
        parent::__construct($message);

        $this->error = null;
        $bFoundFile = false;

        if (XSLProc::getErrors() instanceof MessageStack) {
            foreach (XSLProc::getErrors() as $e) {
                if ($e->type == XSLProc::ERROR_XML) {
                    $this->error = (object) array();
                    $this->error->file = XSLProc::lastXML();
                    $this->error->line = $e->line;
                    $bFoundFile = true;

                    break;
                } elseif (strlen(trim($e->file)) == 0) {
                    continue;
                }

                $this->error = (object) array();
                $this->error->file = $e->file;
                $this->error->line = $e->line;
                $bFoundFile = true;

                break;
            }

            if (is_null($this->error)) {
                foreach (XSLProc::getErrors() as $e) {
                    if (preg_match_all('/(\/?[^\/\s]+\/.+.xsl) line (\d+)/i', $e->message, $matches, PREG_SET_ORDER)) {
                        $this->file = $matches[0][1];
                        $this->line = $matches[0][2];
                        $bFoundFile = true;

                        break;
                    } elseif (preg_match_all('/([^:]+): (.+) line (\d+)/i', $e->message, $matches, PREG_SET_ORDER)) {
                        //throw new Exception("Fix XSLPROC Frontend doesn't have access to Page");

                        $this->line = $matches[0][3];
                        $this->file = Frontend::instance()->loadedView()->pathname;
                        $bFoundFile = true;
                    }
                }
            }
        }

//            var_dump(XSLProc::getErrors()); exit;

/*
        // FIXME: This happens when there is an error in the page XSL. Since it is loaded in to a string then passed to the processor it does not return a file
        if(!$bFoundFile){
            $page = Symphony::parent()->Page()->pageData();
            $this->file = VIEWS . '/' . $page['filelocation'];
            $this->line = 0;

            // Need to look for a potential line number, since
            // it will not have been grabbed
            foreach($errors as $e){
                if($e->line > 0){
                    $this->line = $e->line;
                    break;
                }
            }
        }
*/
    }
}
