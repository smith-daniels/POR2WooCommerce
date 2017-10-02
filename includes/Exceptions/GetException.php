<?php


/**
 * GetException for error handling of failures in a post request
 */

class GetException extends \Exception
{
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}
