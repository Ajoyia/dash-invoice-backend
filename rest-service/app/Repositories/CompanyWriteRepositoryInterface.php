<?php

namespace App\Repositories;

use App\Models\Company;

interface CompanyWriteRepositoryInterface
{
    public function create(array $data): Company;
    public function update(Company $company, array $data): Company;
    public function delete(Company $company): bool;
    public function restore(Company $company): bool;
}
