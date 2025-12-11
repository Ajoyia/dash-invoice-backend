<?php

namespace App\Models\Concerns;

trait CompanyRelations
{
    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class, 'company_id');
    }

    public function bankDetails()
    {
        return $this->hasMany(\App\Models\CompanyBankDetail::class, 'company_id');
    }
}
