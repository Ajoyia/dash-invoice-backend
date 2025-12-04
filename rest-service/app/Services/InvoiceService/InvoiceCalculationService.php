<?php

namespace App\Services\InvoiceService;

use App\Services\InvoiceService\Interfaces\InvoiceCalculationInterface;
use App\Models\Invoice;

class InvoiceCalculationService implements InvoiceCalculationInterface
{
    public function calculateTotalAmount(Invoice $invoice): float
    {
        return round($invoice->netto + $invoice->tax_amount, 2);
    }

    public function calculateTaxAmount(Invoice $invoice): float
    {
        return $invoice->tax_amount ?? 0;
    }

    public function calculateNetAmount(Invoice $invoice): float
    {
        return $invoice->netto ?? 0;
    }

    public function calculateProductTotal(array $product): float
    {
        return $product['quantity'] * $product['credit_price'];
    }

    public function calculateTaxForProduct(array $product): float
    {
        $netAmount = $this->calculateProductTotal($product);
        return $netAmount * ($product['tax'] / 100);
    }
}
