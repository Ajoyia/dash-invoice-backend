<?php

namespace App\Services\InvoiceService\Interfaces;

use App\Models\Invoice;

interface InvoiceNumberGeneratorInterface
{
    public function generateInvoiceNumber(Invoice $invoice): string;
}
