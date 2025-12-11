<?php

namespace App\Repositories\Dashboard;

use App\Models\Company;
use Carbon\Carbon;

class CompanyDashboardRepository implements CompanyDashboardRepositoryInterface
{
    public function find(string $id): ?Company
    {
        return Company::find($id);
    }

    public function getCountByDateRange(array $companyIds, Carbon $start, Carbon $end): int
    {
        return Company::whereIn('id', $companyIds)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    public function getDailyCounts(array $companyIds, Carbon $start, Carbon $end): array
    {
        $data = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $count = Company::whereIn('id', $companyIds)
                ->whereDate('created_at', $current->toDateString())
                ->count();

            $data[] = $count;
            $current->addDay();
        }

        return $data;
    }

    public function getTodayCount(array $companyIds): int
    {
        return Company::whereIn('id', $companyIds)
            ->whereDate('created_at', today())
            ->count();
    }

    public function getThisWeekCount(array $companyIds): int
    {
        return Company::whereIn('id', $companyIds)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
    }

    public function getThisMonthCount(array $companyIds): int
    {
        return Company::whereIn('id', $companyIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function getTotalCount(array $companyIds): int
    {
        return Company::whereIn('id', $companyIds)->count();
    }
}

