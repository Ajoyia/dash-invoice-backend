<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InvoiceRepositoryInterface
{
    public function getInvoices(array $filters, bool $isAdmin): LengthAwarePaginator;

    public function storeInvoice(array $data): Invoice;

    public function updateInvoice(string $id, array $data): Invoice;
}
