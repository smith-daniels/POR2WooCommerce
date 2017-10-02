<?php


/**
 * TermExists for error handling of failures involving trying to create a category again
 * Error Codes:
 */

class TermExists extends \Exception
{
    private $resource; // Holds category ID

    public function __construct($message, $code, $resource)
    {
        parent::__construct($message, $code);

        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }
}
