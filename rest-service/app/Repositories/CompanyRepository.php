<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CompanyRepository implements CompanyNumberGeneratorInterface, CompanyQueryRepositoryInterface, CompanyReadRepositoryInterface, CompanyRepositoryInterface, CompanyWriteRepositoryInterface
{
    public function find(string $id): ?Company
    {
        return Company::find($id);
    }

    public function findOrFail(string $id): Company
    {
        return Company::findOrFail($id);
    }

    public function getAll(): Collection
    {
        return Company::all();
    }

    public function paginate(array $filters, int $perPage = 25, ?string $sortBy = null, ?string $sortOrder = null): LengthAwarePaginator
    {
        $query = Company::query();

        $query->filter($filters);

        if ($sortBy && $sortOrder && $sortBy !== 'createdAt') {
            $query = $this->applySorting($query, $sortBy, $sortOrder);
        }

        if ($sortBy === 'createdAt') {
            $query->orderBy('companies.created_at', $sortOrder ?? 'desc');
        }

        return $query->paginate($perPage);
    }

    public function getCompaniesWithInvoices(array $filters, int $perPage = 25, ?string $sortBy = null, ?string $sortOrder = null, ?string $companyId = null): LengthAwarePaginator
    {
        $query = Company::query()->with('invoices');

        if ($companyId) {
            $query->where('id', $companyId);
        }

        $query->filter($filters);

        if ($sortBy && $sortOrder && $sortBy !== 'createdAt') {
            $query = $this->applySorting($query, $sortBy, $sortOrder);
        }

        if ($sortBy === 'createdAt') {
            $query->orderBy('companies.created_at', $sortOrder ?? 'desc');
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Company
    {
        return Company::create($data);
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->fresh();
    }

    public function delete(Company $company): bool
    {
        return $company->delete();
    }

    public function restore(Company $company): bool
    {
        return $company->restore();
    }

    public function findByCompanyId(string $companyId): ?Company
    {
        return Company::where('id', $companyId)->first();
    }

    public function generateCompanyNumber(): string
    {
        $maxNumber = DB::table('companies')
            ->max(DB::raw('CAST(SUBSTRING(company_number, 2) AS UNSIGNED)')) ?? 1000;

        return 'C'.($maxNumber + 1);
    }

    private function applySorting($query, string $sortBy, string $sortOrder)
    {
        $sortMap = [
            'companyName' => 'company_name',
            'companyNumber' => 'company_number',
            'vatId' => 'vat_id',
            'city' => 'city',
            'country' => 'country',
            'status' => 'status',
        ];

        $column = $sortMap[$sortBy] ?? $sortBy;

        return $query->orderBy($column, $sortOrder);
    }
}
