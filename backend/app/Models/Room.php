<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'block', 'room_no', 'capacity', 'occupied', 'warden',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'occupied' => 'integer',
        ];
    }
}
