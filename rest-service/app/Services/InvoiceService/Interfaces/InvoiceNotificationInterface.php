<?php

namespace App\Services\InvoiceService\Interfaces;

use App\Models\Invoice;

interface InvoiceNotificationInterface
{
    public function sendInvoiceCreatedNotification(Invoice $invoice): void;
    public function sendInvoiceStatusChangedNotification(Invoice $invoice, string $oldStatus, string $newStatus): void;
}
