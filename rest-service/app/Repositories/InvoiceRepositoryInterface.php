<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\Invoice;

interface InvoiceRepositoryInterface
{
    public function getInvoices(array $filters, bool $isAdmin): LengthAwarePaginator;
    public function storeInvoice(array $data): Invoice;
    public function updateInvoice(string $id, array $data): Invoice;
}