<?php

namespace IJIDeals\IJIOrderManagement\Exceptions;

class InvalidOrderItemTypeException extends \InvalidArgumentException // Using InvalidArgumentException as it's more specific
{
    public function __construct($message = "The provided item type is invalid.", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
