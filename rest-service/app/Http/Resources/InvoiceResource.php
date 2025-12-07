<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoiceNumber' => $this->invoice_number,
            'companyId' => $this->company_id,
            'companyName' => $this->company->company_name ?? "",
            'company' =>  $this->company ? [
                'id' => $this->company->id,
                'companyNumber' => $this->company->company_number,
                'companyName' => $this->company->company_name,
                'displayName' => $this->company->display_name,
                'vatId' => $this->company->vat_id,
                'addressLine1' => $this->company->address_line_1,
                'addressLine2' => $this->company->address_line_2,
                'city' => $this->company->city,
                'zipCode' => $this->company->zip_code,
                'country' => $this->company->country,
                'code' => $this->company->zip_code,
                'phone' => $this->company->phone,
                'status' => $this->company->status,
                'invoiceEmailAddress' => $this->company->invoice_email,
                'applyReverseCharge' => $this->company->apply_reverse_charge,
                'externalOrderNumber' => $this->company->external_order_number,
                'warningMailAddress' => $this->company->warning_invoice_email ?? '',
            ] : null,
            'invoiceType' => $this->invoice_type,
            'status' => $this->status,
            'userId' => $this->user_id,
            'dueDate' => Carbon::parse($this->due_date)->toDateString(),
            'startDate' => Carbon::parse($this->start_date)->toDateString(),
            'endDate' => Carbon::parse($this->end_date)->toDateString(),
            'invoiceDate' => Carbon::parse($this->invoice_date)->toDateString(),
            'externalOrderNumber' => $this->external_order_number,
            'customNotesFields' => $this->custom_notes_fields,
            'applyReverseCharge' => $this->apply_reverse_charge,
            'netto' => $this->netto,
            'taxAmount' => $this->tax_amount,
            'totalAmount' => round($this->netto + $this->tax_amount, 2),
            'createdAt' => Carbon::parse($this->created_at)->toDateString(),
            'referenceInvoiceDetail' => $this->referenceInvoice ? [
                'id' => $this->referenceInvoice?->id ?? null,
                'invoiceNumber' => $this->referenceInvoice?->invoice_number ?? null
            ] : null,
            'products' => isset($this->products) ? $this->products->map(function ($product) {
                return [
                    'pos' => $product->pos,
                    'articleNumber' => $product->article_number,
                    'productName' => $product->product_name,
                    'name' => $product->product_name,
                    'quantity' => $product->quantity,
                    'credits' => $product->credits,
                    'totalCredits' => $product->total_credits,
                    'creditPrice' => $product->credit_price,
                    'productPrice' => $product->product_price,
                    'nettoTotal' => $product->product_price,
                    'tax' => $product->tax,
                ];
            }) : [],
        ];
    }
}
