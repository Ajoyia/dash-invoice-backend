<?php

namespace App\Services\InvoiceService;

use App\Services\InvoiceService\Interfaces\InvoiceListingServiceInterface;
use App\Services\InvoiceService\Interfaces\InvoiceManagementServiceInterface;
use App\Services\InvoiceService\Interfaces\InvoiceExportServiceInterface;
use App\Services\InvoiceService\Interfaces\GlobalSettingsServiceInterface;

class InvoiceService
{
    protected $invoiceListingService;
    protected $invoiceManagementService;
    protected $invoiceExportService;
    protected $globalSettingsService;

    public function __construct(
        InvoiceListingServiceInterface $invoiceListingService,
        InvoiceManagementServiceInterface $invoiceManagementService,
        InvoiceExportServiceInterface $invoiceExportService,
        GlobalSettingsServiceInterface $globalSettingsService
    ) {
        $this->invoiceListingService = $invoiceListingService;
        $this->invoiceManagementService = $invoiceManagementService;
        $this->invoiceExportService = $invoiceExportService;
        $this->globalSettingsService = $globalSettingsService;
    }

    public function listInvoices($request, bool $isAdmin)
    {
        return $this->invoiceListingService->listInvoices($request, $isAdmin);
    }

    public function storeInvoice(array $data)
    {
        return $this->invoiceManagementService->storeInvoice($data);
    }

    public function updateInvoice(string $id, array $data)
    {
        return $this->invoiceManagementService->updateInvoice($id, $data);
    }

    public function updateInvoiceStatus($request, string $id)
    {
        return $this->invoiceManagementService->updateInvoiceStatus($request, $id);
    }

    public function deleteInvoice(string $id)
    {
        return $this->invoiceManagementService->deleteInvoice($id);
    }

    public function documentAssignmentList()
    {
        return $this->globalSettingsService->documentAssignmentList();
    }

    public function documentAssignmentSave($request)
    {
        return $this->globalSettingsService->documentAssignmentSave($request);
    }

    public function createCSV($invoices, string $file_name, string $type, bool $is_latest_exported_csv = false)
    {
        return $this->invoiceExportService->createCSV($invoices, $file_name, $type, $is_latest_exported_csv);
    }
}