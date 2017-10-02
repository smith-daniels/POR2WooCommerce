<?php


/**
 * PostException for error handling of failures in a post request
 */

class SQLHashException extends \Exception
{
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}
