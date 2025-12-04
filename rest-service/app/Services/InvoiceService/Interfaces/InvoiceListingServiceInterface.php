<?php

namespace App\Services\InvoiceService\Interfaces;

use Illuminate\Http\Request;

interface InvoiceListingServiceInterface
{
    public function listInvoices(Request $request, bool $isAdmin);
}
