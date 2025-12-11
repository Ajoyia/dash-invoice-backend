<?php

namespace App\Repositories\Dashboard;

use Carbon\Carbon;

interface InvoiceDashboardRepositoryInterface
{
    public function getCountByDateRange(array $companyIds, Carbon $start, Carbon $end): int;

    public function getDailyCounts(array $companyIds, Carbon $start, Carbon $end): array;

    public function getTodayCount(array $companyIds): int;

    public function getThisWeekCount(array $companyIds): int;

    public function getThisMonthCount(array $companyIds): int;

    public function getTotalCount(array $companyIds): int;
}

