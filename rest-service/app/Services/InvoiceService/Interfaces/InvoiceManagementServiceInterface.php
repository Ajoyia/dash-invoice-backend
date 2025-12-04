<?php

namespace App\Services\InvoiceService\Interfaces;

use App\Models\Invoice;
use Illuminate\Http\Request;

interface InvoiceManagementServiceInterface
{
    public function storeInvoice(array $data): Invoice;
    public function updateInvoice(string $id, array $data): Invoice;
    public function deleteInvoice(string $id);
    public function updateInvoiceStatus(Request $request, string $id);
}
