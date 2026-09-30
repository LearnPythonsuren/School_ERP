<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeInvoice extends Model
{
    protected $fillable = [
        'invoice_no',
        'student_id',
        'class_name',
        'amount',
        'status',      // paid | partial | due
        'paid_on',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'paid_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
