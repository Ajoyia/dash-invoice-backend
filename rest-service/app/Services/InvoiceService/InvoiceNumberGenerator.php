<?php

namespace App\Services\InvoiceService;

use App\Services\InvoiceService\Interfaces\InvoiceNumberGeneratorInterface;
use App\Models\Invoice;

class InvoiceNumberGenerator implements InvoiceNumberGeneratorInterface
{
    public function generateInvoiceNumber(Invoice $invoice): string
    {
        if ($invoice->status === 'draft' || $invoice->invoice_number) {
            return $invoice->invoice_number;
        }

        $year = now()->format('y');
        $lastInvoice = Invoice::where('invoice_number', 'like', $year . '-%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice && preg_match('/' . $year . '-(\d+)/', $lastInvoice->invoice_number, $matches)) {
            $nextNumber = (int)$matches[1] + 1;
        } else {
            $nextNumber = 76001;
        }

        return $year . '-' . $nextNumber;
    }
}
