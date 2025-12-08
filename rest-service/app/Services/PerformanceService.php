<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceService
{
    public static function logSlowQueries($threshold = 1000)
    {
        DB::listen(function ($query) use ($threshold) {
            if ($query->time > $threshold) {
                Log::warning('Slow Query Detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time.'ms',
                ]);
            }
        });
    }

    public static function getQueryCount()
    {
        $count = 0;
        DB::listen(function ($query) use (&$count) {
            $count++;
        });

        return $count;
    }

    public static function clearCache()
    {
        Cache::flush();
    }

    public static function getCacheStats()
    {
        return [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
        ];
    }

    public static function optimizeDatabase()
    {
        // Run database optimization commands
        DB::statement('ANALYZE TABLE invoices');
        DB::statement('ANALYZE TABLE companies');
        DB::statement('ANALYZE TABLE invoice_products');
    }
}
