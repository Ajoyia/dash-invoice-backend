<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

interface CompanyReadRepositoryInterface
{
    public function find(string $id): ?Company;
    public function findOrFail(string $id): Company;
    public function getAll(): Collection;
    public function findByCompanyId(string $companyId): ?Company;
}
