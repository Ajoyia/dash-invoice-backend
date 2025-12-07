<?php

namespace App\Services\Company;

interface CompanyRegistrationMailServiceInterface
{
    public function sendRegistrationMail(array $userData): void;
}
