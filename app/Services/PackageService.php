<?php

namespace App\Services;

use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PackageService
{
    public function index()
    {
        $packages = Package::with('prices')->latest()->get();

        return Inertia::render('Packages/Index', [
            'packages' => $packages,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        try {
            DB::transaction(function () use ($validated) {
                $package = Package::create([
                    'name'        => $validated['name'],
                    'choices'     => $validated['choices'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'is_active'   => $validated['is_active'] ?? true,
                ]);

                $this->syncPrices($package, $validated['prices'] ?? []);
            });

            return redirect()->back()->with('success', 'Package saved successfully.');
        } catch (\Throwable $e) {
            Log::error('PackageService@store failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to save package.');
        }
    }

    public function update(Request $request, Package $package)
    {
        $validated = $this->validateRequest($request, $package);

        try {
            DB::transaction(function () use ($validated, $package) {
                $package->update([
                    'name'        => $validated['name'],
                    'choices'     => $validated['choices'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'is_active'   => $validated['is_active'] ?? true,
                ]);

                $this->syncPrices($package, $validated['prices'] ?? []);
            });

            return redirect()->back()->with('success', 'Package updated successfully.');
        } catch (\Throwable $e) {
            Log::error('PackageService@update failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to update package.');
        }
    }

    public function destroy(Package $package)
    {
        try {
            $package->delete();

            return redirect()->back()->with('success', 'Package deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('PackageService@destroy failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to delete package.');
        }
    }

    private function validateRequest(Request $request, ?Package $package = null): array
    {
        return $request->validate([
            'name'          => [
                'required',
                'string',
                'max:191',
                Rule::unique('packages', 'name')->ignore($package?->id),
            ],
            'choices'       => 'nullable|string',
            'description'   => 'nullable|string',
            'is_active'     => 'boolean',
            'prices'        => 'nullable|array',
            'prices.*.pax'  => 'required|integer|min:1',
            'prices.*.price'=> 'required|numeric|min:0',
        ]);
    }

    private function syncPrices(Package $package, array $prices): void
    {
        $package->prices()->delete();

        $seen = [];

        foreach ($prices as $row) {
            $pax = (int) ($row['pax'] ?? 0);

            // Skip invalid or duplicate pax counts so the unique(package_id, pax)
            // constraint is never violated.
            if ($pax <= 0 || isset($seen[$pax])) {
                continue;
            }

            $seen[$pax] = true;

            $package->prices()->create([
                'pax'   => $pax,
                'price' => $row['price'] ?? 0,
            ]);
        }
    }
}
