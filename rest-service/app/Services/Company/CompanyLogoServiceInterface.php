<?php

namespace App\Services\Company;

use App\Models\Company;

interface CompanyLogoServiceInterface
{
    public function uploadLogo(Company $company, $image): void;
    public function deleteLogo(Company $company): void;
}
