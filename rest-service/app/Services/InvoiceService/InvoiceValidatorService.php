<?php

namespace App\Services\InvoiceService;

use App\Services\InvoiceService\Interfaces\InvoiceValidatorInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceValidatorService implements InvoiceValidatorInterface
{
    public function validateInvoiceData(array $data): bool
    {
        $rules = [
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
            "products" => "required|array",
            'products.*.productName' => 'required|string',
            'products.*.quantity' => 'required',
            'products.*.tax' => 'required',
            'products.*.totalCredits' => 'required',
            'products.*.credits' => 'required',
            'products.*.creditPrice' => 'required',
            'products.*.nettoTotal' => 'required'
        ];

        $validator = Validator::make($data, $rules);
        return !$validator->fails();
    }

    public function validateInvoiceStatusUpdate(Request $request): bool
    {
        $rules = [
            "status" => "required|in:approved,sent,paid,warning level 1,warning level 2,warning level 3",
        ];

        $validator = Validator::make($request->all(), $rules);
        return !$validator->fails();
    }

    public function getValidationErrors(array $data): array
    {
        $rules = [
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
            "products" => "required|array",
            'products.*.productName' => 'required|string',
            'products.*.quantity' => 'required',
            'products.*.tax' => 'required',
            'products.*.totalCredits' => 'required',
            'products.*.credits' => 'required',
            'products.*.creditPrice' => 'required',
            'products.*.nettoTotal' => 'required'
        ];

        $validator = Validator::make($data, $rules);
        return $validator->errors()->toArray();
    }
}
