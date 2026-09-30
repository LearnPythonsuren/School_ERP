<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'bus_no', 'route_name', 'driver', 'capacity',
        'status', 'lat', 'lng', 'speed_kph', 'last_ping',
    ];

    protected function casts(): array
    {
        return [
            'capacity'  => 'integer',
            'lat'       => 'float',
            'lng'       => 'float',
            'speed_kph' => 'integer',
            'last_ping' => 'datetime',
        ];
    }
}
