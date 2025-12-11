<?php

namespace App\Services\DashboardService;

use App\Helpers\Helper;
use App\Repositories\Dashboard\CompanyDashboardRepositoryInterface;
use App\Repositories\Dashboard\InvoiceDashboardRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardService implements DashboardServiceInterface
{
    protected $companyRepository;
    protected $invoiceRepository;

    public function __construct(
        CompanyDashboardRepositoryInterface $companyRepository,
        InvoiceDashboardRepositoryInterface $invoiceRepository
    ) {
        $this->companyRepository = $companyRepository;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function portalDashboard(Request $request)
    {
        try {
            $companyId = Helper::getCompanyId($request->bearerToken());
            [$startDate, $endDate] = $this->parseDateRange($request);
            $companyIds = [$companyId];

            $invoiceLabels = $this->generateDateLabels($startDate, $endDate);
            $invoiceSeries = $this->invoiceRepository->getDailyCounts($companyIds, $startDate, $endDate);

            $summary = [
                'today' => $this->invoiceRepository->getTodayCount($companyIds),
                'thisWeek' => $this->invoiceRepository->getThisWeekCount($companyIds),
                'thisMonth' => $this->invoiceRepository->getThisMonthCount($companyIds),
                'total' => $this->invoiceRepository->getTotalCount($companyIds),
                'invoices' => $this->invoiceRepository->getCountByDateRange($companyIds, $startDate, $endDate),
            ];

            return response()->json(array_merge($summary, [
                'labels' => $invoiceLabels,
                'series' => [['name' => 'Invoices', 'data' => $invoiceSeries]],
            ]));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function backOfficeDashboard(Request $request)
    {
        try {
            $companyId = $request->customerId ?? Helper::getCompanyId($request->bearerToken());
            [$startDate, $endDate] = $this->parseDateRange($request);
            $relatedCompanyIds = $this->companyRepository->find($companyId)->parent_id;
            if ($relatedCompanyIds) {
                $relatedCompanyIds = [$relatedCompanyIds];
            } else {
                $relatedCompanyIds = [$companyId];
            }

            $invoiceLabels = $this->generateDateLabels($startDate, $endDate);
            $customerLabels = $this->generateDateLabels($startDate, $endDate);

            $invoiceSeries = $this->invoiceRepository->getDailyCounts($relatedCompanyIds, $startDate, $endDate);
            $customerSeries = $this->companyRepository->getDailyCounts($relatedCompanyIds, $startDate, $endDate);

            $invoiceSummary = [
                'today' => $this->invoiceRepository->getTodayCount($relatedCompanyIds),
                'thisWeek' => $this->invoiceRepository->getThisWeekCount($relatedCompanyIds),
                'thisMonth' => $this->invoiceRepository->getThisMonthCount($relatedCompanyIds),
                'total' => $this->invoiceRepository->getTotalCount($relatedCompanyIds),
                'invoices' => $this->invoiceRepository->getCountByDateRange($relatedCompanyIds, $startDate, $endDate),
            ];

            $customerSummary = [
                'today' => $this->companyRepository->getTodayCount($relatedCompanyIds),
                'thisWeek' => $this->companyRepository->getThisWeekCount($relatedCompanyIds),
                'thisMonth' => $this->companyRepository->getThisMonthCount($relatedCompanyIds),
                'total' => $this->companyRepository->getTotalCount($relatedCompanyIds),
                'customers' => $this->companyRepository->getCountByDateRange($relatedCompanyIds, $startDate, $endDate),
            ];

            return response()->json([
                'invoices' => array_merge($invoiceSummary, [
                    'labels' => $invoiceLabels,
                    'series' => $invoiceSeries
                ]),
                'customers' => array_merge($customerSummary, [
                    'labels' => $customerLabels,
                    'series' => $customerSeries
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    protected function parseDateRange(Request $request): array
    {
        $start = $request->input('startDate') 
            ? Carbon::parse($request->input('startDate'))->startOfDay() 
            : Carbon::now()->startOfDay();

        $end = $request->input('endDate') 
            ? Carbon::parse($request->input('endDate'))->endOfDay() 
            : Carbon::now()->endOfDay();

        return [$start, $end];
    }

    protected function generateDateLabels(Carbon $start, Carbon $end): array
    {
        $labels = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $labels[] = $current->toDateString();
            $current->addDay();
        }

        return $labels;
    }
}

