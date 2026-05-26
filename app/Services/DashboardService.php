<?php

namespace App\Services;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Order;
use App\Models\User;
use App\Models\Menu;
use App\Models\Table;
use Carbon\Carbon;

class DashboardService
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'orders_today' => Order::whereDate('created_at', Carbon::today())->count(),
                'users_count' => User::count(),
                'menus_count' => Menu::count(),
                'tables_count' => Table::count(),
            ]
        ]);
    }
}