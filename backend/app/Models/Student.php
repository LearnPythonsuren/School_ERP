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

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** Derive fee_status from the student's invoices (no invoices = leave as is). */
    public function refreshFeeStatus(): void
    {
        $invoices = $this->invoices()->get(['amount', 'paid_amount']);
        if ($invoices->isEmpty()) {
            return;
        }
        $billed = (float) $invoices->sum('amount');
        $paid   = (float) $invoices->sum('paid_amount');

        $this->update(['fee_status' => $paid >= $billed ? 'paid' : ($paid > 0 ? 'partial' : 'due')]);
    }

    /** Recompute attendance % from every marked day. */
    public function refreshAttendancePct(): void
    {
        $total = $this->attendance()->count();
        if ($total === 0) {
            return;
        }
        $present = $this->attendance()->where('status', 'present')->count();
        $this->update(['attendance_pct' => (int) round($present * 100 / $total)]);
    }
}
