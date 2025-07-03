<?php

namespace IJIDeals\IJIOrderManagement\Exceptions;

class ProductAssociationException extends \RuntimeException
{
    public function __construct($message = "Product or variation is not correctly associated with the shop.", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
