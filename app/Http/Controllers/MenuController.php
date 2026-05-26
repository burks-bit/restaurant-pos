<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MenuService;
use Illuminate\Support\Facades\Log;

class MenuController extends Controller
{
    protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function index()
    {
        try {
            return $this->menuService->index();
        } catch (\Throwable $e) {
            Log::error('MenuController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load menus.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->menuService->store($request);
        } catch (\Throwable $e) {
            Log::error('MenuController@store failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to create menu.');
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            return $this->menuService->update($request, $id);
        } catch (\Throwable $e) {
            Log::error('MenuController@update failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to update menu.');
        }
    }
}