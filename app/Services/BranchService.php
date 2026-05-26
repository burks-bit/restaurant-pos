<?php

namespace App\Services;

use App\Models\Branch;
use Inertia\Inertia;

class BranchService
{
    public function index()
    {
        $branches = Branch::get();

        return Inertia::render('Branches/Index', compact('branches'));
    }
}