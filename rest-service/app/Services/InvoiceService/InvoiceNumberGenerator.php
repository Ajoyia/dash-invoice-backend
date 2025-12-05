<?php

namespace App\Services\InvoiceService;

use App\Services\InvoiceService\Interfaces\InvoiceNumberGeneratorInterface;
use App\Models\Invoice;

class InvoiceNumberGenerator implements InvoiceNumberGeneratorInterface
{
    private const STARTING_INVOICE_NUMBER = 76001;

    public function generateInvoiceNumber(Invoice $invoice): string
    {
        if ($invoice->status === 'draft' || $invoice->invoice_number) {
            return $invoice->invoice_number ?? '';
        }

        $year = now()->format('y');
        $pattern = $year . '-%';
        
        $lastInvoice = Invoice::where('invoice_number', 'like', $pattern)
            ->orderByRaw('CAST(SUBSTRING_INDEX(invoice_number, "-", -1) AS UNSIGNED) DESC')
            ->value('invoice_number');

        if ($lastInvoice && preg_match('/' . preg_quote($year, '/') . '-(\d+)/', $lastInvoice, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = self::STARTING_INVOICE_NUMBER;
        }

        return $year . '-' . $nextNumber;
    }
}
