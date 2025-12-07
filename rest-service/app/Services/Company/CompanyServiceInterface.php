<?php

namespace App\Services\Company;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface CompanyServiceInterface
{
    public function getAllCompanies(Request $request, bool $isAdmin): LengthAwarePaginator;
    public function getCompanyById(string $id, Request $request): array;
    public function createCompany(array $data, Request $request): Company;
    public function updateCompany(string $id, array $data, Request $request): Company;
    public function deleteCompany(string $id): bool;
    public function restoreCompany(string $id): bool;
    public function getCompanyCredits(string $companyId): float;
}
