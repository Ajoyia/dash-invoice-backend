<?php

namespace App\Models\Concerns;

trait CompanyBusinessLogic
{
    protected static function booted()
    {
        static::creating(function ($company) {
            $company->valid_from = now();
            $company->valid_to = '9999-12-31 00:00:00';
            $company->changed_by = null;
        });
    }
}
