<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingManagementController extends Controller
{
    /**
     * Update the status of a booking in real time.
     */
    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,confirmed,in_progress,completed,cancelled'],
        ]);

        $booking->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', "El turno {$booking->booking_code} fue actualizado a estado '{$booking->status_label}'.");
    }

    /**
     * Remove or cancel a booking permanently.
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        $code = $booking->booking_code;
        $booking->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', "Turno {$code} eliminado del sistema.");
    }
}
