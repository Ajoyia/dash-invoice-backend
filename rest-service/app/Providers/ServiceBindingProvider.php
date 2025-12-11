<?php

namespace App\Providers;

use App\Repositories\CompanyRepository;
use App\Repositories\CompanyRepositoryInterface;
use App\Repositories\Dashboard\CompanyDashboardRepository;
use App\Repositories\Dashboard\CompanyDashboardRepositoryInterface;
use App\Repositories\Dashboard\InvoiceDashboardRepository;
use App\Repositories\Dashboard\InvoiceDashboardRepositoryInterface;
use App\Services\Company\CompanyExportService;
use App\Services\Company\CompanyExportServiceInterface;
use App\Services\Company\CompanyLogoService;
use App\Services\Company\CompanyLogoServiceInterface;
use App\Services\Company\CompanyRegistrationMailService;
use App\Services\Company\CompanyRegistrationMailServiceInterface;
use App\Services\Company\CompanyService;
use App\Services\Company\CompanyServiceInterface;
use App\Services\Company\VatValidationService;
use App\Services\Company\VatValidationServiceInterface;
use App\Services\DashboardService\DashboardService;
use App\Services\DashboardService\DashboardServiceInterface;
use App\Services\Queue\QueueServiceInterface;
use App\Services\Queue\RedisQueueService;
use Illuminate\Support\ServiceProvider;

class ServiceBindingProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(CompanyServiceInterface::class, CompanyService::class);
        $this->app->bind(VatValidationServiceInterface::class, VatValidationService::class);
        $this->app->bind(CompanyLogoServiceInterface::class, CompanyLogoService::class);
        $this->app->bind(CompanyExportServiceInterface::class, CompanyExportService::class);
        $this->app->bind(CompanyRegistrationMailServiceInterface::class, CompanyRegistrationMailService::class);
        $this->app->bind(QueueServiceInterface::class, RedisQueueService::class);
        $this->app->bind(CompanyDashboardRepositoryInterface::class, CompanyDashboardRepository::class);
        $this->app->bind(InvoiceDashboardRepositoryInterface::class, InvoiceDashboardRepository::class);
        $this->app->bind(DashboardServiceInterface::class, DashboardService::class);
    }

    public function boot(): void {}
}
