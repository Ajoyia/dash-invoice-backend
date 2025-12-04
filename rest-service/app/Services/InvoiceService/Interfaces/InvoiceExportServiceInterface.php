<?php

namespace App\Services\InvoiceService\Interfaces;

interface InvoiceExportServiceInterface
{
    public function createCSV($invoices, $file_name, $type, $is_latest_exported_csv = false);
}
