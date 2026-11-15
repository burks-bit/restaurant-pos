<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Services\PackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    protected $packageService;

    public function __construct(PackageService $packageService)
    {
        $this->packageService = $packageService;
    }

    public function index()
    {
        try {
            return $this->packageService->index();
        } catch (\Throwable $e) {
            Log::error('PackageController@index failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to load packages.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->packageService->store($request);
        } catch (\Throwable $e) {
            Log::error('PackageController@store failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to save package.');
        }
    }

    public function update(Request $request, Package $package)
    {
        try {
            return $this->packageService->update($request, $package);
        } catch (\Throwable $e) {
            Log::error('PackageController@update failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to update package.');
        }
    }

    public function destroy(Package $package)
    {
        try {
            return $this->packageService->destroy($package);
        } catch (\Throwable $e) {
            Log::error('PackageController@destroy failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete package.');
        }
    }
}
