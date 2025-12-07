<?php

namespace App\Repositories;

interface CompanyNumberGeneratorInterface
{
    public function generateCompanyNumber(): string;
}
