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

    public function index()
    {
        try {
            return $this->reservationService->index();
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