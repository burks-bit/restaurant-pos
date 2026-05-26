<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Table;
use App\Services\TableService;
use Illuminate\Support\Facades\Log;

class TableController extends Controller
{
    protected $tableService;

    public function __construct(TableService $tableService)
    {
        $this->tableService = $tableService;
    }

    public function index()
    {
        try {
            return $this->tableService->index();
        } catch (\Throwable $e) {
            Log::error('TableController@index failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load tables.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->tableService->store($request);
        } catch (\Throwable $e) {
            Log::error('TableController@store failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to create table.');
        }
    }

    public function update(Request $request, Table $table)
    {
        try {
            return $this->tableService->update($request, $table);
        } catch (\Throwable $e) {
            Log::error('TableController@update failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to update table.');
        }
    }

    public function destroy(Table $table)
    {
        try {
            return $this->tableService->destroy($table);
        } catch (\Throwable $e) {
            Log::error('TableController@destroy failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete table.');
        }
    }

    public function get_tables()
    {
        try {
            return $this->tableService->get_tables();
        } catch (\Throwable $e) {
            Log::error('TableController@get_tables failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load table occupancy.');
        }
    }

    public function get_tables_for_admission(Request $request)
    {
        try {
            return $this->tableService->get_tables_for_admission($request);
        } catch (\Throwable $e) {
            Log::error('TableController@get_tables_for_admission failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to load table occupancy.');
        }
    }

    public function vacantTable(Request $request, Table $table)
    {
        try {
            return $this->tableService->vacantTable($request, $table);
        } catch (\Throwable $e) {
            Log::error('TableController@vacantTable failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update table status.'
            ], 500);
        }
    }

    public function cancelQueuedTable(Request $request, Table $table)
    {
        try {
            return $this->tableService->cancelQueuedTable($request, $table);
        } catch (\Throwable $e) {
            Log::error('TableController@cancelQueuedTable failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to cancel queued table.'
            ], 500);
        }
    }

    public function assignTable(Request $request, $tableId)
    {
        try {
            return $this->tableService->assignTable($request, $tableId);
        } catch (\Throwable $e) {
            Log::error('TableController@assignTable failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to assign table.'
            ], 500);
        }
    }

    public function admitTable(Request $request, $tableId = null)
    {
        try {
            return $this->tableService->admitTable($request, $tableId);
        } catch (\Throwable $e) {
            Log::error('TableController@admitTable failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'route_table_id' => $tableId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign table.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOpenSessions()
    {
        try {
            return $this->tableService->getOpenSessions();
        } catch (\Throwable $e) {
            Log::error('TableController@getOpenSessions failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch open sessions.'
            ], 500);
        }
    }
}