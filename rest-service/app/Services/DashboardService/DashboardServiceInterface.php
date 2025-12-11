<?php

namespace App\Services\DashboardService;

use Illuminate\Http\Request;

interface DashboardServiceInterface
{
    public function portalDashboard(Request $request);

    public function backOfficeDashboard(Request $request);
}

