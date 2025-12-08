<?php

namespace App\Services\Company;

use App\Helpers\Helper;
use App\Models\Company;

class CompanyLogoService implements CompanyLogoServiceInterface
{
    public function uploadLogo(Company $company, $image): void
    {
        Helper::removeAttachment($company);
        Helper::saveAttachment($image, $company);
    }

    public function deleteLogo(Company $company): void
    {
        Helper::removeAttachment($company);
    }
}
