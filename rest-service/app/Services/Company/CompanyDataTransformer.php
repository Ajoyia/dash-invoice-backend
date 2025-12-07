<?php

namespace App\Services\Company;

use App\Models\Company;
use Carbon\Carbon;

class CompanyDataTransformer
{
    public function transformForList(Company $company): array
    {
        $invoiceSum = $this->calculateInvoiceSum($company);

        return [
            'id' => $company->id,
            'companyNumber' => $company->company_number,
            'companyName' => $company->company_name,
            'displayName' => $company->display_name,
            'addressLine1' => $company->address_line_1,
            'vatId' => $company->vat_id,
            'zipCode' => $company->zip_code,
            'preferredContactLanguage' => $company->contact_language,
            'city' => $company->city,
            'credits' => $company->credits,
            'status' => $company->status,
            'invoiceSum' => $invoiceSum,
            'country' => $company->country,
            'applyReverseCharge' => $company->apply_reverse_charge,
            'externalOrderNumber' => $company->external_order_number,
            'phone' => $company->phone ?? '',
            'invoiceEmailAddress' => $company->invoice_email ?? '',
            'warningMailAddress' => $company->warning_invoice_email ?? '',
            'createdAt' => Carbon::parse($company->created_at)->format('Y-m-d'),
        ];
    }

    public function transformForDetail(Company $company): array
    {
        return [
            'id' => $company->id,
            'companyName' => $company->company_name,
            'displayName' => $company->display_name,
            'vatId' => $company->vat_id,
            'addressLine1' => $company->address_line_1,
            'addressLine2' => $company->address_line_2,
            'city' => $company->city,
            'credits' => $company->credits,
            'country' => $company->country,
            'zipCode' => $company->zip_code,
            'preferredContactLanguage' => $company->contact_language,
            'phone' => $company->phone,
            'status' => $company->status,
            'invoiceEmailAddress' => $company->invoice_email,
            'applyReverseCharge' => $company->apply_reverse_charge,
            'freeCases' => $company->free_cases_count,
            'externalOrderNumber' => $company->external_order_number,
            'warningMailAddress' => $company->warning_invoice_email ?? '',
            'notificationMail' => $company->notification_mail ?? '',
            'bankDetails' => $this->transformBankDetails($company),
        ];
    }

    private function transformBankDetails(Company $company): array
    {
        if (!$company->relationLoaded('bankDetails') || !$company->bankDetails) {
            return [];
        }

        return $company->bankDetails->map(function ($bankDetail) {
            return [
                'bankName' => $bankDetail->bank_name,
                'swift' => $bankDetail->swift,
                'iban' => $bankDetail->iban,
                'routing_number' => $bankDetail->routing_number,
                'account_number' => $bankDetail->account_number,
                'institution_number' => $bankDetail->institution_number,
                'transit_number' => $bankDetail->transit_number,
                'bsb_code' => $bankDetail->bsb_code,
                'branch_code' => $bankDetail->branch_code,
                'bank_code' => $bankDetail->bank_code,
                'country_name' => $bankDetail->country_name,
            ];
        })->toArray();
    }

    private function calculateInvoiceSum(Company $company): float
    {
        try {
            if ($company->relationLoaded('invoices')) {
                return collect($company->invoices)->sum('total_amount');
            }
            return $company->invoices()->sum('total_amount');
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}
