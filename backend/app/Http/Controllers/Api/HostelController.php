<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HostelController extends Controller
{
    public function index()
    {
        return Room::orderBy('block')->orderBy('room_no')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'block'    => ['required', 'string', 'max:20'],
            'room_no'  => ['required', 'string', 'max:20', Rule::unique('rooms')->where('block', $request->input('block'))],
            'capacity' => ['required', 'integer', 'min:1'],
            'occupied' => ['nullable', 'integer', 'min:0', 'lte:capacity'],
            'warden'   => ['nullable', 'string', 'max:120'],
        ]);
        return response()->json(Room::create($data), 201);
    }

    public function show(Room $room)
    {
        return response()->json($room);
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'block'    => ['sometimes', 'string', 'max:20'],
            'room_no'  => ['sometimes', 'string', 'max:20',
                Rule::unique('rooms')->where('block', $request->input('block', $room->block))->ignore($room->id)],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'occupied' => ['sometimes', 'integer', 'min:0'],
            'warden'   => ['nullable', 'string', 'max:120'],
        ]);
        $capacity = $data['capacity'] ?? $room->capacity;
        $occupied = $data['occupied'] ?? $room->occupied;
        abort_if($occupied > $capacity, 422, "Occupancy ({$occupied}) cannot exceed capacity ({$capacity}).");

        $room->update($data);
        return response()->json($room);
    }

    public function destroy(Room $room)
    {
        abort_if($room->occupied > 0, 422, 'Vacate the room before deleting it.');
        $room->delete();
        return response()->noContent();
    }

    public function allot(Room $room)
    {
        abort_if($room->occupied >= $room->capacity, 422, 'Room is full.');
        $room->increment('occupied');
        return response()->json($room);
    }

    public function vacate(Room $room)
    {
        abort_if($room->occupied < 1, 422, 'Room is already empty.');
        $room->decrement('occupied');
        return response()->json($room);
    }
}
