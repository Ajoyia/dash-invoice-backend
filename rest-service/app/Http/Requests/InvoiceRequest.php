<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'companyId' => 'required|exists:companies,id',
            'referenceInvoiceId' => 'nullable|exists:invoices,id',
            'invoiceType' => 'required|in:invoice-correction,invoice,invoice-storno',
            'status' => 'required|in:draft,approved,sent,warning level 1,warning level 2,warning level 3,paid',
            'dueDate' => 'required|date',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'invoiceDate' => 'nullable|date',
            'externalOrderNumber' => 'nullable|string',
            'customNotesFields' => 'nullable',
            'applyReverseCharge' => 'nullable|boolean',
            'netto' => 'nullable',
            'taxAmount' => 'nullable',
            'totalAmount' => 'nullable',
            'products' => 'required|array',
            'products.*.productName' => 'required|string',
            'products.*.quantity' => 'required',
            'products.*.tax' => 'required',
            'products.*.totalCredits' => 'required',
            'products.*.credits' => 'required',
            'products.*.creditPrice' => 'required',
            'products.*.nettoTotal' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'products.*.pos.required' => 'The pos field is required.',
            'products.*.productName.required' => 'The product name field is required.',
            'products.*.productPrice.required' => 'The product price field is required.',
            'products.*.tax.required' => 'The tax field is required.',
            'products.*.quantity.required' => 'The quantity field is required.',
            'products.*.nettoTotal.required' => 'The netto total field is required.',
        ];
    }
}
