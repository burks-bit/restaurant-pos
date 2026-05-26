<?php

namespace App\Services;

use App\Models\HeadPricingRule;
use App\Models\PricingScheme;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class HeadPricingRuleService
{
    public function index()
    {
        $pricingSchemes = PricingScheme::with([
            'headRules' => function ($query) {
                $query->orderBy('id');
            }
        ])
        ->orderBy('id')
        ->get()
        ->map(function ($scheme) {
            return [
                'id' => $scheme->id,
                'name' => $scheme->name,
                'type' => $scheme->type,
                'description' => $scheme->description,
                'is_active' => $scheme->is_active,
                'created_at' => $scheme->created_at,
                'updated_at' => $scheme->updated_at,
                'head_rules' => $scheme->headRules->map(function ($rule) {
                    return [
                        'id' => $rule->id,
                        'pricing_scheme_id' => $rule->pricing_scheme_id,
                        'code' => $rule->code,
                        'label' => $rule->label,
                        'min_age' => $rule->min_age,
                        'max_age' => $rule->max_age,
                        'min_height' => $rule->min_height,
                        'max_height' => $rule->max_height,
                        'price' => $rule->price,
                        'is_active' => $rule->is_active,
                        'created_at' => $rule->created_at,
                        'updated_at' => $rule->updated_at,
                    ];
                })->values(),
            ];
        });

        return Inertia::render('HeadPricing/HeadPricingRules', [
            'pricing_schemes' => $pricingSchemes,
        ]);
    }

    public function storePricingScheme(array $data): PricingScheme
    {
        return DB::transaction(function () use ($data) {
            return PricingScheme::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);
        });
    }

    public function updatePricingScheme(PricingScheme $pricingScheme, array $data): PricingScheme
    {
        return DB::transaction(function () use ($pricingScheme, $data) {
            $pricingScheme->update([
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            return $pricingScheme->fresh();
        });
    }

    public function store(array $data): HeadPricingRule
    {
        return DB::transaction(function () use ($data) {
            return HeadPricingRule::create([
                'pricing_scheme_id' => $data['pricing_scheme_id'],
                'label' => $data['label'],
                'min_age' => $data['min_age'] ?? null,
                'max_age' => $data['max_age'] ?? null,
                'min_height' => $data['min_height'] ?? null,
                'max_height' => $data['max_height'] ?? null,
                'price' => $data['price'],
                'is_active' => $data['is_active'],
            ]);
        });
    }

    public function update(HeadPricingRule $headPricingRule, array $data): HeadPricingRule
    {
        return DB::transaction(function () use ($headPricingRule, $data) {
            $headPricingRule->update([
                'pricing_scheme_id' => $data['pricing_scheme_id'],
                'label' => $data['label'],
                'min_age' => $data['min_age'] ?? null,
                'max_age' => $data['max_age'] ?? null,
                'min_height' => $data['min_height'] ?? null,
                'max_height' => $data['max_height'] ?? null,
                'price' => $data['price'],
                'is_active' => $data['is_active'],
            ]);

            return $headPricingRule->fresh();
        });
    }

    public function destroy(HeadPricingRule $headPricingRule): void
    {
        DB::transaction(function () use ($headPricingRule) {
            $headPricingRule->delete();
        });
    }
}