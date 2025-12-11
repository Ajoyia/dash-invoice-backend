<?php

namespace App\Http\Controllers;

use App\Services\DashboardService\DashboardServiceInterface;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardServiceInterface $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     summary="Get portal dashboard data",
     *     description="Retrieve dashboard statistics and metrics for invoices with date filtering",
     *     operationId="PortalDashboard",
     *     tags={"Dashboard"},
     *     @OA\Parameter(
     *          name="startDate",
     *          description="Filter by start date",
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              format="date",
     *              example="2024-01-01"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="endDate",
     *          description="Filter by end date",
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              format="date",
     *              example="2024-12-31"
     *          ),
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *     )
     * )
     */
    public function index(Request $request)
    {
        return $this->dashboardService->portalDashboard($request);
    }

    /**
     * @OA\Get(
     *     path="/api/backoffice-dashboard",
     *     summary="Get backoffice dashboard data",
     *     description="Retrieve dashboard statistics for invoices and customers with date filtering",
     *     operationId="BackofficeDashboard",
     *     tags={"Dashboard"},
     *     @OA\Parameter(
     *          name="startDate",
     *          description="Filter by start date",
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              format="date",
     *              example="2024-01-01"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="endDate",
     *          description="Filter by end date",
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              format="date",
     *              example="2024-12-31"
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="customerId",
     *          description="Filter by customer ID",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          ),
     *      ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *     )
     * )
     */
    public function backOfficeDashboard(Request $request)
    {
        return $this->dashboardService->backOfficeDashboard($request);
    }
}
