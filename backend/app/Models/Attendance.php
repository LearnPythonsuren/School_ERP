<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'student_id',
        'date',
        'status',   // present | absent | leave
    ];

    // `date` is deliberately left uncast: it is stored as a plain Y-m-d
    // string so the unique (student_id, date) upsert matches on every driver.

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
