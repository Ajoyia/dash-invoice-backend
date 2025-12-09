<?php

namespace App\Exceptions;

use Exception;

class InvoiceNotFoundException extends Exception
{
    public function __construct(string $message = 'Invoice not found', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
