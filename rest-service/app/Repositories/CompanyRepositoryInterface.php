<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CompanyRepositoryInterface
{
    public function find(string $id): ?Company;
    public function findOrFail(string $id): Company;
    public function getAll(): Collection;
    public function paginate(array $filters, int $perPage = 25, ?string $sortBy = null, ?string $sortOrder = null): LengthAwarePaginator;
    public function create(array $data): Company;
    public function update(Company $company, array $data): Company;
    public function delete(Company $company): bool;
    public function restore(Company $company): bool;
    public function findByCompanyId(string $companyId): ?Company;
    public function generateCompanyNumber(): string;
    public function getCompaniesWithInvoices(array $filters, int $perPage = 25, ?string $sortBy = null, ?string $sortOrder = null, ?string $companyId = null): LengthAwarePaginator;
}
