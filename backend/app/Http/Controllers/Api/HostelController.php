<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class HostelController extends Controller
{
    public function index()
    {
        return Room::orderBy('block')->orderBy('room_no')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'block'    => ['required', 'string'],
            'room_no'  => ['required', 'string'],
            'capacity' => ['required', 'integer', 'min:1'],
            'occupied' => ['nullable', 'integer', 'min:0'],
            'warden'   => ['nullable', 'string'],
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
            'block'    => ['sometimes', 'string'],
            'room_no'  => ['sometimes', 'string'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'occupied' => ['sometimes', 'integer', 'min:0'],
            'warden'   => ['nullable', 'string'],
        ]);
        $room->update($data);
        return response()->json($room);
    }

    public function destroy(Room $room)
    {
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
