<?php

namespace App\Services\InvoiceService;

use App\Services\InvoiceService\Interfaces\InvoiceValidatorInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceValidatorService implements InvoiceValidatorInterface
{
    private const INVOICE_TYPES = ['invoice-correction', 'invoice', 'invoice-storno'];

    private const INVOICE_STATUSES = ['draft', 'approved', 'sent', 'warning level 1', 'warning level 2', 'warning level 3', 'paid'];

    private const UPDATEABLE_STATUSES = ['approved', 'sent', 'paid', 'warning level 1', 'warning level 2', 'warning level 3'];

    private function getInvoiceValidationRules(): array
    {
        return [
            'companyId' => 'required|exists:companies,id',
            'referenceInvoiceId' => 'nullable|exists:invoices,id',
            'invoiceType' => 'required|in:'.implode(',', self::INVOICE_TYPES),
            'status' => 'required|in:'.implode(',', self::INVOICE_STATUSES),
            'dueDate' => 'required|date',
            'startDate' => 'required|date',
            'endDate' => 'required|date',
            'invoiceDate' => 'nullable|date',
            'externalOrderNumber' => 'nullable|string',
            'customNotesFields' => 'nullable',
            'applyReverseCharge' => 'nullable|boolean',
            'netto' => 'nullable|numeric',
            'taxAmount' => 'nullable|numeric',
            'totalAmount' => 'nullable|numeric',
            'products' => 'required|array|min:1',
            'products.*.productName' => 'required|string',
            'products.*.quantity' => 'required|numeric|min:0',
            'products.*.tax' => 'required|numeric|min:0',
            'products.*.totalCredits' => 'required|numeric|min:0',
            'products.*.credits' => 'required|numeric|min:0',
            'products.*.creditPrice' => 'required|numeric|min:0',
            'products.*.nettoTotal' => 'required|numeric|min:0',
        ];
    }

    public function validateInvoiceData(array $data): bool
    {
        $validator = Validator::make($data, $this->getInvoiceValidationRules());

        return ! $validator->fails();
    }

    public function validateInvoiceStatusUpdate(Request $request): bool
    {
        $rules = [
            'status' => 'required|in:'.implode(',', self::UPDATEABLE_STATUSES),
        ];

        $validator = Validator::make($request->all(), $rules);

        return ! $validator->fails();
    }

    public function getValidationErrors(array $data): array
    {
        $validator = Validator::make($data, $this->getInvoiceValidationRules());

        return $validator->errors()->toArray();
    }
}
