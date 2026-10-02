<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * Store a new car wash appointment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_id'     => ['required', 'exists:services,id'],
            'customer_name'  => ['required', 'string', 'max:150'],
            'customer_email' => ['required', 'email', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'vehicle_type'   => ['required', 'string', 'max:50'],
            'vehicle_plate'  => ['required', 'string', 'max:15'],
            'vehicle_color'  => ['nullable', 'string', 'max:50'],
            'booking_date'   => ['required', 'date', 'after_or_equal:today'],
            'booking_time'   => ['required', 'string', 'max:20'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $service = Service::findOrFail($validated['service_id']);

        // Generate unique readable tracking code, e.g., AW-A7B2X9
        do {
            $code = 'AW-' . strtoupper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        $booking = Booking::create([
            'booking_code'   => $code,
            'user_id'        => auth()->id(), // null if guest
            'service_id'     => $service->id,
            'customer_name'  => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'vehicle_type'   => $validated['vehicle_type'],
            'vehicle_plate'  => strtoupper(trim($validated['vehicle_plate'])),
            'vehicle_color'  => $validated['vehicle_color'] ?? null,
            'booking_date'   => $validated['booking_date'],
            'booking_time'   => $validated['booking_time'],
            'status'         => 'pending',
            'total_price'    => $service->price,
            'notes'          => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('home')
            ->with('booking_success', [
                'code'    => $booking->booking_code,
                'name'    => $booking->customer_name,
                'plate'   => $booking->vehicle_plate,
                'service' => $service->name,
                'date'    => $booking->booking_date->format('d/m/Y'),
                'time'    => $booking->booking_time,
                'price'   => $booking->formatted_price,
            ]);
    }

    /**
     * Search and track booking status by vehicle plate or booking code.
     */
    public function tracking(Request $request)
    {
        $query = strtoupper(trim($request->input('search', '')));

        if (! $query) {
            return redirect()->route('home')->with('tracking_error', 'Ingresa una placa o código de reserva para consultar.');
        }

        $booking = Booking::with('service')
            ->where('booking_code', $query)
            ->orWhere('vehicle_plate', $query)
            ->latest()
            ->first();

        if (! $booking) {
            return redirect()->route('home')->with('tracking_error', "No se encontró ningún turno activo con el identificador '{$query}'.");
        }

        return redirect()->route('home')->with('tracked_booking', $booking);
    }
}
