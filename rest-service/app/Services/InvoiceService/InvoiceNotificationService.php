<?php

namespace App\Services\InvoiceService;

use App\Models\Invoice;
use App\Services\InvoiceService\Interfaces\InvoiceNotificationInterface;
use Illuminate\Support\Facades\Log;

class InvoiceNotificationService implements InvoiceNotificationInterface
{
    public function sendInvoiceCreatedNotification(Invoice $invoice): void
    {
        Log::info('Invoice created', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'company_id' => $invoice->company_id,
            'amount' => $invoice->total_amount,
        ]);
    }

    public function sendInvoiceStatusChangedNotification(Invoice $invoice, string $oldStatus, string $newStatus): void
    {
        Log::info('Invoice status changed', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'company_id' => $invoice->company_id,
        ]);
    }

    public function sendInvoicePaidNotification(Invoice $invoice): void
    {
        Log::info('Invoice paid', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'company_id' => $invoice->company_id,
            'amount' => $invoice->total_amount,
            'paid_at' => $invoice->paid_at,
        ]);
    }
}
