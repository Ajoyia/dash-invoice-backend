<?php

namespace App\Models\Concerns;

trait CompanyRelations
{
    public function resellerCompany()
    {
        return $this->belongsTo(\App\Models\Company::class, 'reseller_id');
    }

    public function salesPartner()
    {
        return $this->belongsTo(\App\Models\Company::class, 'sales_partner_id');
    }

    public function referrals()
    {
        return $this->hasMany(\App\Models\Company::class, 'referral_id');
    }

    public function invoices()
    {
        return $this->hasMany(\App\Models\Invoice::class, 'company_id');
    }

    public function servicePartner()
    {
        return $this->belongsTo(\App\Models\Company::class, 'service_partner_id');
    }

    public function bankDetails()
    {
        return $this->hasMany(\App\Models\CompanyBankDetail::class, 'company_id');
    }
}
