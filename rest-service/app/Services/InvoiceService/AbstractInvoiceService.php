<?php

namespace App\Services\InvoiceService;

use App\Repositories\InvoiceRepositoryInterface;

abstract class AbstractInvoiceService
{
    protected $invoiceRepository;

    public function __construct(InvoiceRepositoryInterface $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    abstract public function getServiceName(): string;
}
