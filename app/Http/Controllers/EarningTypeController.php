<?php

namespace App\Http\Controllers;

use App\Models\EarningType;
use App\Services\EarningTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EarningTypeController extends Controller
{
    protected $earningTypeService;

    public function __construct(EarningTypeService $earningTypeService)
    {
        $this->earningTypeService = $earningTypeService;
    }

    public function index()
    {
        try {
            return $this->earningTypeService->index();
        } catch (\Throwable $e) {
            Log::error('EarningTypeController@index failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to load earning types.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->earningTypeService->store($request);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('EarningTypeController@store failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to save earning type.');
        }
    }

    public function update(Request $request, EarningType $earningType)
    {
        try {
            return $this->earningTypeService->update($request, $earningType);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('EarningTypeController@update failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to update earning type.');
        }
    }

    public function destroy(EarningType $earningType)
    {
        try {
            return $this->earningTypeService->destroy($earningType);
        } catch (\Throwable $e) {
            Log::error('EarningTypeController@destroy failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete earning type.');
        }
    }
}
