<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HeadPricingRule;
use Inertia\Inertia;

class HeadPricingRuleController extends Controller
{
    public function index()
    {
        $rules = HeadPricingRule::orderBy('id', 'asc')->get();
        return Inertia::render('HeadPricing/HeadPricingRules', [
            'rules' => $rules
        ]);
    }
}
