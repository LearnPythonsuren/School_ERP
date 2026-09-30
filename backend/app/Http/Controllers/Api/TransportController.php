<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    public function index()
    {
        return Vehicle::orderBy('bus_no')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bus_no'     => ['required', 'string'],
            'route_name' => ['nullable', 'string'],
            'driver'     => ['nullable', 'string'],
            'capacity'   => ['nullable', 'integer', 'min:1'],
            'status'     => ['nullable', 'in:on_route,idle,maintenance'],
        ]);
        return response()->json(Vehicle::create($data), 201);
    }

    public function show(Vehicle $vehicle)
    {
        return response()->json($vehicle);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'bus_no'     => ['sometimes', 'string'],
            'route_name' => ['nullable', 'string'],
            'driver'     => ['nullable', 'string'],
            'capacity'   => ['sometimes', 'integer', 'min:1'],
            'status'     => ['sometimes', 'in:on_route,idle,maintenance'],
        ]);
        $vehicle->update($data);
        return response()->json($vehicle);
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return response()->noContent();
    }

    /** GPS ping from the driver app. */
    public function updateLocation(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'lat'       => ['required', 'numeric', 'between:-90,90'],
            'lng'       => ['required', 'numeric', 'between:-180,180'],
            'speed_kph' => ['nullable', 'integer', 'min:0'],
        ]);
        $vehicle->update([
            'lat' => $data['lat'], 'lng' => $data['lng'],
            'speed_kph' => $data['speed_kph'] ?? 0, 'status' => 'on_route', 'last_ping' => now(),
        ]);
        return response()->json($vehicle);
    }
}
