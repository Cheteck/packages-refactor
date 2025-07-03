<?php

namespace IJIDeals\IJIOrderManagement\Exceptions;

// Use LogicException for errors in program logic, like trying to add a base product that has variations.
class OrderLogicException extends \LogicException
{
    public function __construct($message = "There was a logical error processing the order.", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
