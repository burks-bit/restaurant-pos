<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Response;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(): Response
    {
        return $this->dashboardService->index();
    }
}