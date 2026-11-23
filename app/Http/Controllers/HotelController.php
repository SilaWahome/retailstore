<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FolioCharge;
use App\Models\FolioPayment;
use App\Models\GuestFolio;
use App\Models\RestaurantOrder;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HotelController extends Controller
{
    public function rooms()
    {
        $tenant = auth()->user()->tenant;

        $rooms = Room::with([
            'roomType',
            'activeBooking.folio',
        ])->orderBy('room_number')->get();

        $roomTypes = RoomType::orderBy('name')->get();

        $upcomingBookings = Booking::with('room')
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->orderBy('check_in_date')
            ->get();

        return Inertia::render('Hotel/Rooms', [
            'rooms' => $rooms,
            'roomTypes' => $roomTypes,
            'bookings' => $upcomingBookings,
            'roomLimit' => $tenant?->roomLimit(),
            'canFolio' => $tenant?->canAccess('guest_folio') ?? false,
            'canChargeToRoom' => $tenant?->canAccess('charge_to_room') ?? false,
            'canAdvanceCalendar' => $tenant?->canAccess('advance_calendar') ?? false,
        ]);
    }

    public function storeRoomType(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'base_price_ugx' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1|max:20',
            'description' => 'nullable|string',
        ]);

        RoomType::create([
            'tenant_id' => auth()->user()->tenant_id,
            ...$data,
        ]);

        return back()->with('success', 'Room type created.');
    }

    public function storeRoom(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $limit = $tenant?->roomLimit();

        if ($limit !== null && Room::count() >= $limit) {
            return back()->withErrors([
                'room_limit' => "Your current plan allows up to {$limit} rooms. Upgrade to Hotel & Resort Pro (UGX 250,000/mo) for unlimited rooms.",
            ]);
        }

        $data = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => 'required|string|max:20',
            'floor' => 'nullable|string|max:20',
        ]);

        Room::create([
            'tenant_id' => $tenant->id,
            'room_type_id' => $data['room_type_id'],
            'room_number' => $data['room_number'],
            'floor' => $data['floor'] ?? null,
            'status' => 'available',
        ]);

        return back()->with('success', 'Room added.');
    }

    public function updateRoomStatus(Request $request, Room $room)
    {
        $request->validate([
            'status' => 'required|in:available,cleaning,maintenance,reserved,occupied',
            'housekeeper_note' => 'nullable|string|max:191',
        ]);

        $room->update([
            'status' => $request->status,
            'housekeeper_note' => $request->housekeeper_note ?? $room->housekeeper_note,
        ]);

        return response()->json(['room' => $room->fresh('roomType')]);
    }

    public function storeBooking(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'guest_name' => 'required|string|max:150',
            'guest_phone' => 'required|string|max:50',
            'guest_id_number' => 'nullable|string|max:100',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'nightly_rate_ugx' => 'nullable|numeric|min:0',
            'check_in_now' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $room = Room::with('roomType')->findOrFail($data['room_id']);
        $rate = $data['nightly_rate_ugx'] ?? $room->roomType->base_price_ugx;
        $checkInNow = (bool) ($data['check_in_now'] ?? false);

        DB::transaction(function () use ($data, $room, $rate, $checkInNow) {
            $booking = Booking::create([
                'tenant_id' => auth()->user()->tenant_id,
                'room_id' => $room->id,
                'guest_name' => $data['guest_name'],
                'guest_phone' => $data['guest_phone'],
                'guest_id_number' => $data['guest_id_number'] ?? null,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'nightly_rate_ugx' => $rate,
                'status' => $checkInNow ? 'checked_in' : 'confirmed',
                'notes' => $data['notes'] ?? null,
                'checked_in_at' => $checkInNow ? now() : null,
            ]);

            if ($checkInNow) {
                $this->initializeFolioForBooking($booking);
                $room->update(['status' => 'occupied']);
            } else {
                if ($room->status === 'available') {
                    $room->update(['status' => 'reserved']);
                }
            }
        });

        return back()->with('success', $checkInNow ? 'Guest checked in and folio opened.' : 'Reservation confirmed.');
    }

    public function checkIn(Booking $booking)
    {
        if ($booking->status !== 'confirmed') {
            return back()->withErrors(['error' => 'Only confirmed bookings can be checked in.']);
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'checked_in',
                'checked_in_at' => now(),
            ]);
            $this->initializeFolioForBooking($booking);
            $booking->room->update(['status' => 'occupied']);
        });

        return back()->with('success', 'Guest checked in.');
    }

    public function checkOut(Booking $booking)
    {
        if ($booking->status !== 'checked_in') {
            return back()->withErrors(['error' => 'Booking is not currently checked in.']);
        }

        DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'checked_out',
                'checked_out_at' => now(),
            ]);
            $booking->room->update(['status' => 'cleaning']);
        });

        return back()->with('success', 'Guest checked out. Room marked for housekeeping.');
    }

    public function showFolio(Booking $booking)
    {
        $booking->load(['room.roomType', 'folio.charges', 'folio.payments']);

        if (!$booking->folio) {
            $this->initializeFolioForBooking($booking);
            $booking->load('folio.charges', 'folio.payments');
        }

        return Inertia::render('Hotel/Folio', [
            'booking' => $booking,
        ]);
    }

    public function addFolioCharge(Request $request, GuestFolio $folio)
    {
        $data = $request->validate([
            'source' => 'required|in:room_rate,restaurant_pos,laundry,bar,extra_bed,other',
            'description' => 'required|string|max:255',
            'amount_ugx' => 'required|numeric|min:1',
        ]);

        DB::transaction(function () use ($data, $folio) {
            FolioCharge::create([
                'folio_id' => $folio->id,
                'source' => $data['source'],
                'description' => $data['description'],
                'amount_ugx' => $data['amount_ugx'],
                'created_by' => auth()->id(),
            ]);

            $folio->increment('total_charges_ugx', $data['amount_ugx']);
            $folio->update([
                'is_settled' => $folio->total_paid_ugx >= $folio->total_charges_ugx,
            ]);
        });

        return back()->with('success', 'Charge posted to guest folio.');
    }

    public function addFolioPayment(Request $request, GuestFolio $folio)
    {
        $data = $request->validate([
            'amount_ugx' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,mobile_money,card,bank_transfer',
            'reference' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($data, $folio) {
            FolioPayment::create([
                'folio_id' => $folio->id,
                'amount_ugx' => $data['amount_ugx'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'received_by' => auth()->id(),
            ]);

            $folio->increment('total_paid_ugx', $data['amount_ugx']);
            $folio->refresh();
            $folio->update([
                'is_settled' => (float) $folio->total_paid_ugx >= (float) $folio->total_charges_ugx,
            ]);
        });

        return back()->with('success', 'Payment recorded on folio.');
    }

    public function chargeToRoom(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:restaurant_orders,id',
            'room_id' => 'required|exists:rooms,id',
        ]);

        $result = DB::transaction(function () use ($request) {
            $booking = Booking::where('room_id', $request->room_id)
                ->where('status', 'checked_in')
                ->with(['folio', 'room'])
                ->firstOrFail();

            $order = RestaurantOrder::with('table')->findOrFail($request->order_id);

            $folio = $booking->folio ?: $this->initializeFolioForBooking($booking);

            $folio->charges()->create([
                'source' => 'restaurant_pos',
                'reference_id' => 'ORD-' . $order->id,
                'description' => 'Restaurant Dine-in (Order #' . $order->id . ')',
                'amount_ugx' => $order->total_amount_ugx,
                'created_by' => auth()->id(),
            ]);

            $folio->increment('total_charges_ugx', $order->total_amount_ugx);
            $folio->update(['is_settled' => false]);

            $order->update([
                'status' => 'posted_to_room',
                'payment_status' => 'posted_to_room',
                'payment_method' => 'room_folio',
                'paid_amount_ugx' => $order->total_amount_ugx,
                'closed_at' => now(),
            ]);

            if ($order->table) {
                $order->table->update([
                    'status' => 'available',
                    'current_order_id' => null,
                ]);
            }

            return [
                'status' => 'success',
                'message' => 'Charged UGX ' . number_format((float) $order->total_amount_ugx) . ' to Room ' . $booking->room->room_number,
                'guest' => $booking->guest_name,
                'room_number' => $booking->room->room_number,
                'order_id' => $order->id,
                'amount_ugx' => (float) $order->total_amount_ugx,
            ];
        });

        return response()->json($result);
    }

    private function initializeFolioForBooking(Booking $booking): GuestFolio
    {
        $nights = max(1, Carbon::parse($booking->check_in_date)->diffInDays(Carbon::parse($booking->check_out_date)));
        $roomCharge = $nights * (float) $booking->nightly_rate_ugx;

        $folio = GuestFolio::create([
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'total_charges_ugx' => $roomCharge,
            'total_paid_ugx' => 0,
            'is_settled' => false,
        ]);

        FolioCharge::create([
            'folio_id' => $folio->id,
            'source' => 'room_rate',
            'reference_id' => 'BK-' . $booking->id,
            'description' => "Room Stay ({$nights} night" . ($nights > 1 ? 's' : '') . " @ UGX " . number_format((float) $booking->nightly_rate_ugx) . ")",
            'amount_ugx' => $roomCharge,
            'created_by' => auth()->id(),
        ]);

        return $folio;
    }
}

