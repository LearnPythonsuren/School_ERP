<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableSlot extends Model
{
    protected $table = 'timetable_slots';

    protected $fillable = [
        'class_name',
        'day',         // Mon | Tue | Wed | Thu | Fri | Sat
        'period_no',
        'subject',
        'teacher',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'period_no' => 'integer',
        ];
    }
}
