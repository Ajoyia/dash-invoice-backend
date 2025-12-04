<?php

namespace App\Services\InvoiceService\Interfaces;

use Illuminate\Http\Request;

interface InvoiceValidatorInterface
{
    public function validateInvoiceData(array $data): bool;
    public function validateInvoiceStatusUpdate(Request $request): bool;
}
