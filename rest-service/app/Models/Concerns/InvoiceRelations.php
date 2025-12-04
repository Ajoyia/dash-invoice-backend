<?php

namespace App\Models\Concerns;

trait InvoiceRelations
{
    public function products()
    {
        return $this->hasMany(\App\Models\InvoiceProduct::class, 'invoice_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    public function referenceInvoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class, 'reference_invoice_id');
    }
}
