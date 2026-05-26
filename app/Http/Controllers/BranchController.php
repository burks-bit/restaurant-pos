<?php

namespace App\Http\Controllers;

use App\Services\BranchService;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function index()
    {
        try {
            return $this->branchService->index();
        } catch (\Throwable $e) {
            Log::error('BranchController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load branches.');
        }
    }
}