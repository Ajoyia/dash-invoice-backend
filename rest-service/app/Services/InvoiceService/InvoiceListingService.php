<?php

namespace App\Services\InvoiceService;

use App\Helpers\Helper;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService\Interfaces\InvoiceListingServiceInterface;
use Illuminate\Http\Request;

class InvoiceListingService extends AbstractInvoiceService implements InvoiceListingServiceInterface
{
    public function getServiceName(): string
    {
        return 'InvoiceListingService';
    }

    public function listInvoices(Request $request, bool $isAdmin)
    {
        $companyId = Helper::getCompanyId($request->bearerToken());

        $filters = [
            'perPage' => $request->perPage ?? 25,
            'sortBy' => $request->sortBy,
            'sortOrder' => $request->sortOrder,
            'companyId' => $companyId,
            'status' => $request->status,
            'isReference' => $request->has('isReference') || $request->isReference,
            'invoiceType' => $request->invoiceType,
            'company' => $request->company,
        ];

        $invoices = $this->invoiceRepository->getInvoices($filters, $isAdmin);

        return response()->json([
            'data' => InvoiceResource::collection($invoices),
            'links' => $invoices->linkCollection(),
            'current_page' => $invoices->currentPage(),
            'from' => $invoices->firstItem(),
            'last_page' => $invoices->lastPage(),
            'path' => $request->url(),
            'per_page' => $invoices->perPage(),
            'to' => $invoices->lastItem(),
            'total' => $invoices->total(),
        ]);
    }
}
