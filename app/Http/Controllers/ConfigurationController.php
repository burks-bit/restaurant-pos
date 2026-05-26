<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConfigurationController extends Controller
{
    protected $configurationService;

    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    public function index()
    {
        try {
            return $this->configurationService->index();
        } catch (\Throwable $e) {
            Log::error('ConfigurationController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load configurations.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->configurationService->store($request);
        } catch (\Throwable $e) {
            Log::error('ConfigurationController@store failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to create configuration.');
        }
    }

    public function update(Request $request, Configuration $configuration)
    {
        try {
            return $this->configurationService->update($request, $configuration);
        } catch (\Throwable $e) {
            Log::error('ConfigurationController@update failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to update configuration.');
        }
    }
}
