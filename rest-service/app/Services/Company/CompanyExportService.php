<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Traits\CustomHelper;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyExportService implements CompanyExportServiceInterface
{
    use CustomHelper;

    public function exportToCsv(Collection $companies, string $fileName): StreamedResponse
    {
        $columns = [
            'Company Number',
            'Company Name',
            'Creation Date',
            'Status',
        ];

        $columnMap = [
            'Company Number' => fn(Company $company) => $company->company_number ?? '',
            'Company Name' => fn(Company $company) => $company->company_name,
            'Creation Date' => fn(Company $company) => $this->formatDate($company->created_at, 'de', 'EUR'),
            'Status' => fn(Company $company) => $company->status,
        ];

        $callback = function () use ($companies, $columns, $columnMap) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);

            foreach ($companies as $company) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = $columnMap[$column]($company);
                }
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\""
        ]);
    }
}
