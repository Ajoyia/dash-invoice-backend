<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'companyNumber' => $this->company_number,
            'companyName' => $this->company_name,
            'displayName' => $this->display_name,
            'vatId' => $this->vat_id,
            'addressLine1' => $this->address_line_1,
            'addressLine2' => $this->address_line_2,
            'city' => $this->city,
            'country' => $this->country,
            'zipCode' => $this->zip_code,
            'preferredContactLanguage' => $this->contact_language,
            'phone' => $this->phone,
            'credits' => $this->credits,
            'status' => $this->status,
            'invoiceEmailAddress' => $this->invoice_email,
            'warningMailAddress' => $this->warning_invoice_email ?? '',
            'notificationMail' => $this->notification_mail ?? '',
            'applyReverseCharge' => $this->apply_reverse_charge,
            'externalOrderNumber' => $this->external_order_number,
            'freeCases' => $this->free_cases_count,
            'createdAt' => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d') : null,
            'bankDetails' => $this->whenLoaded('bankDetails', function () {
                return $this->bankDetails->map(function ($bankDetail) {
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
                });
            }),
        ];
    }
}
