<?php

namespace App\Exceptions;

use Exception;

class CompanyNotFoundException extends Exception
{
    public function __construct(string $message = 'Company not found', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}

