<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BranchService;

class BranchController extends Controller
{
    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    public function index()
    {
        return $this->branchService->index();
    }
}