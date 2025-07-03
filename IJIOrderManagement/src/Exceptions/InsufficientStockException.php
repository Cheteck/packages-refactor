<?php

namespace IJIDeals\IJIOrderManagement\Exceptions;

class InsufficientStockException extends \RuntimeException
{
    // You can add custom properties or methods if needed
    // For example, to hold the item ID or requested quantity.

    public function __construct($message = "Insufficient stock for the requested item.", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
