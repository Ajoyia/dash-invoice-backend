<?php

namespace App\Services\InvoiceService;

use App\Services\InvoiceService\Interfaces\InvoiceExportServiceInterface;
use App\Traits\CustomHelper;
use Carbon\Carbon;

class InvoiceExportService implements InvoiceExportServiceInterface
{
    use CustomHelper;

    private const DEFAULT_LOCALE = 'de';
    private const DEFAULT_CURRENCY = 'EUR';

    public function createCSV($invoices, $file_name, $type, $is_latest_exported_csv = false)
    {
        $columns = [
            'Invoice Number',
            'Invoice Type',
            'Status',
            'Total Amount Without Tax',
            'Total Tax Amount',
            'Total Amount',
            'Company',
            'Start Date',
            'End Date',
            'Due Date',
            'Draft Status Changed Date',
            'Apply Reverse Charge'
        ];

        $locale = self::DEFAULT_LOCALE;
        $currency = self::DEFAULT_CURRENCY;

        $columnMap = [
            'Invoice Number' => fn($inv) => $inv->invoice_number ?? '',
            'Invoice Type' => fn($inv) => $inv->invoice_type,
            'Status' => fn($inv) => $inv->status,
            'Total Amount' => fn($inv) => $this->formatNumber($inv->total_amount, $locale, $currency),
            'Total Amount Without Tax' => fn($inv) => $this->formatNumber($inv->netto ?? 0, $locale, $currency),
            'Total Tax Amount' => fn($inv) => $this->formatNumber($inv->tax_amount, $locale, $currency),
            'Company' => fn($inv) => $inv->company->company_name ?? '',
            'Start Date' => fn($inv) => $this->formatDate($inv->start_date, $locale, $currency),
            'End Date' => fn($inv) => $this->formatDate($inv->end_date, $locale, $currency),
            'Due Date' => fn($inv) => $this->formatDate($inv->due_date, $locale, $currency),
            'Draft Status Changed Date' => fn($inv) => $this->formatDate($inv->invoice_date, $locale, $currency),
            'Apply Reverse Charge' => fn($inv) => $inv->apply_reverse_charge ? 'true' : 'false',
        ];

        $callback = function () use ($invoices, $columns, $columnMap) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);

            foreach ($invoices as $invoice) {
                $row = array_map(fn($column) => $columnMap[$column]($invoice), $columns);
                fputcsv($file, $row);
            }

            fclose($file);
        };


        return response()->streamDownload($callback, $file_name, [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$file_name",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ]);
    }
}
