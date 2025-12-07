<?php

namespace App\Providers;

use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
    }

    public function boot(): void
    {
    }
}