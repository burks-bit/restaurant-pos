<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReservationService;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function index(Request $request, ReservationService $reservationService)
    {
        if (!$request->has('start_date') && !$request->has('end_date')) {
            // first load, no query params at all → default to today for both
            $startDate = now()->format('Y-m-d');
            $endDate   = now()->format('Y-m-d');
        } else {
            // params present — could be real dates or '' for "show all"
            $startDate = $request->query('start_date');
            $endDate   = $request->query('end_date');
        }

        try {
            return $reservationService->index($startDate, $endDate);
        } catch (\Throwable $e) {
            Log::error('ReservationController@index failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to load reservations.');
        }
    }

    public function store(Request $request)
    {
        try {
            return $this->reservationService->store($request);
        } catch (\Throwable $e) {
            Log::error('ReservationController@store failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to save reservation.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $reservation = Reservation::findOrFail($id);
            return $this->reservationService->update($request, $reservation);
        } catch (\Throwable $e) {
            Log::error('ReservationController@update failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to update reservation.');
        }
    }

    public function destroy($id)
    {
        try {
            return $this->reservationService->destroy($id);
        } catch (\Throwable $e) {
            Log::error('ReservationController@destroy failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to delete reservation.');
        }
    }
}