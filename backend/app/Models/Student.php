<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'name',
        'class_name',
        'roll_no',
        'fee_status',      // paid | partial | due
        'attendance_pct',
        'guardian_name',
        'guardian_phone',
    ];

    protected function casts(): array
    {
        return [
            'roll_no'        => 'integer',
            'attendance_pct' => 'integer',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(FeeInvoice::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
