<?php

namespace App\Providers;

use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use OpenTelemetry\Contrib\Logs\Monolog\Handler as OTelHandler;
use OpenTelemetry\API\Globals as OTelGlobals;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        require_once base_path('bootstrap/otel.php');

        if (app()->bound('log')) {
            $loggerProvider = OTelGlobals::loggerProvider();
            $otelHandler = new OTelHandler($loggerProvider, Logger::DEBUG);
            \Log::getLogger()->pushHandler($otelHandler);
        }
    }
}