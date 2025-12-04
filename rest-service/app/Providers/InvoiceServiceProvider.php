<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\InvoiceService\Interfaces\InvoiceListingServiceInterface;
use App\Services\InvoiceService\Interfaces\InvoiceManagementServiceInterface;
use App\Services\InvoiceService\Interfaces\InvoiceExportServiceInterface;
use App\Services\InvoiceService\Interfaces\GlobalSettingsServiceInterface;
use App\Services\InvoiceService\Interfaces\InvoiceNumberGeneratorInterface;
use App\Services\InvoiceService\Interfaces\InvoiceValidatorInterface;
use App\Services\InvoiceService\Interfaces\InvoiceNotificationInterface;
use App\Services\InvoiceService\Interfaces\InvoiceCalculationInterface;
use App\Services\InvoiceService\InvoiceListingService;
use App\Services\InvoiceService\InvoiceManagementService;
use App\Services\InvoiceService\InvoiceExportService;
use App\Services\InvoiceService\GlobalSettingsService;
use App\Services\InvoiceService\InvoiceNumberGenerator;
use App\Services\InvoiceService\InvoiceCalculationService;
use App\Services\InvoiceService\InvoiceValidatorService;
use App\Services\InvoiceService\InvoiceNotificationService;

class InvoiceServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(InvoiceListingServiceInterface::class, InvoiceListingService::class);
        $this->app->bind(InvoiceManagementServiceInterface::class, InvoiceManagementService::class);
        $this->app->bind(InvoiceExportServiceInterface::class, InvoiceExportService::class);
        $this->app->bind(GlobalSettingsServiceInterface::class, GlobalSettingsService::class);
        $this->app->bind(InvoiceNumberGeneratorInterface::class, InvoiceNumberGenerator::class);
        $this->app->bind(InvoiceValidatorInterface::class, InvoiceValidatorService::class);
        $this->app->bind(InvoiceNotificationInterface::class, InvoiceNotificationService::class);
        $this->app->bind(InvoiceCalculationInterface::class, InvoiceCalculationService::class);
    }

    public function boot()
    {
        //
    }
}
