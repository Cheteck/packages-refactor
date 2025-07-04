<?php

namespace IJIDeals\VirtualCoin\Exceptions;

class DuplicateTransactionException extends \Exception
{
    // You can add more specific properties or methods if needed,
    // for example, the existing transaction that caused the duplication.
    protected $existingTransaction;

    public function __construct($message = "", $code = 0, \Throwable $previous = null, $existingTransaction = null)
    {
        parent::__construct($message, $code, $previous);
        $this->existingTransaction = $existingTransaction;
    }

    public function getExistingTransaction()
    {
        return $this->existingTransaction;
    }
}
