<?php

namespace App\Http\Controllers;

use App\Models\PricingScheme;
use App\Models\HeadPricingRule;
use App\Services\HeadPricingRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HeadPricingRuleController extends Controller
{
    protected $headPricingRuleService;

    public function __construct(HeadPricingRuleService $headPricingRuleService)
    {
        $this->headPricingRuleService = $headPricingRuleService;
    }

    public function index()
    {
        try {
            return $this->headPricingRuleService->index();
        } catch (\Throwable $e) {
            Log::error('HeadPricingRuleController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load head pricing rules.');
        }
    }

    public function storePricingScheme(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        try {
            $this->headPricingRuleService->storePricingScheme($validated);

            return redirect()
                ->route('admin.head-pricing-rules.index')
                ->with('success', 'Pricing scheme created successfully.');
        } catch (\Throwable $e) {
            Log::error('HeadPricingRuleController@storePricingScheme failed: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Failed to create pricing scheme.');
        }
    }

    public function updatePricingScheme(Request $request, PricingScheme $pricingScheme)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        try {
            $this->headPricingRuleService->updatePricingScheme($pricingScheme, $validated);

            return redirect()
                ->route('admin.head-pricing-rules.index')
                ->with('success', 'Pricing scheme updated successfully.');
        } catch (\Throwable $e) {
            Log::error('HeadPricingRuleController@updatePricingScheme failed: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Failed to update pricing scheme.');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pricing_scheme_id' => ['required', 'exists:pricing_schemes,id'],
            'label' => ['required', 'string', 'max:255'],
            'min_age' => ['nullable', 'integer', 'min:0'],
            'max_age' => ['nullable', 'integer', 'min:0'],
            'min_height' => ['nullable', 'numeric', 'min:0'],
            'max_height' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        try {
            $this->headPricingRuleService->store($validated);

            return redirect()
                ->route('admin.head-pricing-rules.index')
                ->with('success', 'Head pricing rule created successfully.');
        } catch (\Throwable $e) {
            Log::error('HeadPricingRuleController@store failed: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Failed to create head pricing rule.');
        }
    }

    public function update(Request $request, HeadPricingRule $headPricingRule)
    {
        $validated = $request->validate([
            'pricing_scheme_id' => ['required', 'exists:pricing_schemes,id'],
            'label' => ['required', 'string', 'max:255'],
            'min_age' => ['nullable', 'integer', 'min:0'],
            'max_age' => ['nullable', 'integer', 'min:0'],
            'min_height' => ['nullable', 'numeric', 'min:0'],
            'max_height' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        try {
            $this->headPricingRuleService->update($headPricingRule, $validated);

            return redirect()
                ->route('admin.head-pricing-rules.index')
                ->with('success', 'Head pricing rule updated successfully.');
        } catch (\Throwable $e) {
            Log::error('HeadPricingRuleController@update failed: ' . $e->getMessage());

            return back()->withInput()->with('error', 'Failed to update head pricing rule.');
        }
    }

    public function destroy(HeadPricingRule $headPricingRule)
    {
        try {
            $this->headPricingRuleService->destroy($headPricingRule);

            return redirect()
                ->route('admin.head-pricing-rules.index')
                ->with('success', 'Head pricing rule deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('HeadPricingRuleController@destroy failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete head pricing rule.');
        }
    }
}