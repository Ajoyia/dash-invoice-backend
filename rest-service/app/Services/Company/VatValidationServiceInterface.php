<?php

namespace App\Services\Company;

interface VatValidationServiceInterface
{
    public function validate(string $vatNumber): array;
}
