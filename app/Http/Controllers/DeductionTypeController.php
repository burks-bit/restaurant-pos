<?php

namespace App\Http\Controllers;

use App\Models\DeductionType;
use App\Services\DeductionTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DeductionTypeController extends Controller
{
    protected $deductionTypeService;

    public function __construct(DeductionTypeService $deductionTypeService)
    {
        $this->deductionTypeService = $deductionTypeService;
    }

    public function index()
    {
        try {
            return $this->deductionTypeService->index();
        } catch (\Throwable $e) {
            Log::error('DeductionTypeController@index failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to load deduction types.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->deductionTypeService->store($request);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('DeductionTypeController@store failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to save deduction type.');
        }
    }

    public function update(Request $request, DeductionType $deductionType)
    {
        try {
            return $this->deductionTypeService->update($request, $deductionType);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('DeductionTypeController@update failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to update deduction type.');
        }
    }

    public function destroy(DeductionType $deductionType)
    {
        try {
            return $this->deductionTypeService->destroy($deductionType);
        } catch (\Throwable $e) {
            Log::error('DeductionTypeController@destroy failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete deduction type.');
        }
    }
}
