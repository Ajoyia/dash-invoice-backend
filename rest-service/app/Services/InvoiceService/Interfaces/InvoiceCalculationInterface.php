<?php

namespace App\Services\InvoiceService\Interfaces;

use App\Models\Invoice;

interface InvoiceCalculationInterface
{
    public function calculateTotalAmount(Invoice $invoice): float;
    public function calculateTaxAmount(Invoice $invoice): float;
    public function calculateNetAmount(Invoice $invoice): float;
}
