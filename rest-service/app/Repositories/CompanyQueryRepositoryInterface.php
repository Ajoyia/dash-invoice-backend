<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CompanyQueryRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 25, ?string $sortBy = null, ?string $sortOrder = null): LengthAwarePaginator;

    public function getCompaniesWithInvoices(array $filters, int $perPage = 25, ?string $sortBy = null, ?string $sortOrder = null, ?string $companyId = null): LengthAwarePaginator;
}
