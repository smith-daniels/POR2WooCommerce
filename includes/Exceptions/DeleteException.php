<?php


/**
 * DeleteException for error handling of failures in a DELETE request
 */

class DeleteException extends \Exception
{
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}
