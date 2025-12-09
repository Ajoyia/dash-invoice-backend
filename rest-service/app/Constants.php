<?php

namespace App;

class Constants
{
    const PERMISSIONS = [
        460 => 'backoffice-company.list',
        461 => 'backoffice-company.create',
        462 => 'backoffice-company.edit',
        463 => 'backoffice-company.delete',
        464 => 'backoffice-company.show-all',
        465 => 'backoffice-company.import-csv',
        491 => 'backoffice-invoice.list',
        492 => 'backoffice-invoice.create',
        493 => 'backoffice-invoice.edit',
        494 => 'backoffice-invoice.delete',
        495 => 'backoffice-invoice.show-all',
        496 => 'backoffice-invoice.export-csv',
        497 => 'backoffice-invoice.export-csv-latest',
        498 => 'backoffice-invoice.export-invoice-plan',
        543 => 'backoffice-invoice-status.edit',
    ];

    const INVOICE_TYPES = [
        'INVOICE' => 'invoice',
        'INVOICE_CORRECTION' => 'invoice-correction',
        'INVOICE_STORNO' => 'invoice-storno',
    ];

    const INVOICE_STATUSES = [
        'DRAFT' => 'draft',
        'APPROVED' => 'approved',
        'SENT' => 'sent',
        'WARNING_LEVEL_1' => 'warning level 1',
        'WARNING_LEVEL_2' => 'warning level 2',
        'WARNING_LEVEL_3' => 'warning level 3',
        'PAID' => 'paid',
    ];

    const COMPANY_STATUSES = [
        'NEW' => 'new',
        'ACTIVE' => 'active',
        'INACTIVE' => 'inactive',
    ];

    const DEFAULT_PAGINATION = 25;
}
