<?php

namespace App\Services\Company;

use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface CompanyExportServiceInterface
{
    public function exportToCsv(Collection $companies, string $fileName): StreamedResponse;
}
