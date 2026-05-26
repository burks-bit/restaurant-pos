<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        try {
            return $this->dashboardService->index();
        } catch (\Throwable $e) {
            Log::error('DashboardController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load dashboard.');
        }
    }
}