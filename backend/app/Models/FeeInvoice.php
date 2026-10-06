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
        'description',
        'amount',
        'paid_amount',
        'status',      // paid | partial | due
        'due_on',
        'paid_on',
        'payment_mode',
        'receipt_no',
    ];

    protected $appends = ['balance'];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_on'      => 'date:Y-m-d',
            'paid_on'     => 'date:Y-m-d',
        ];
    }

    public function getBalanceAttribute(): string
    {
        return number_format(max(0, (float) $this->amount - (float) $this->paid_amount), 2, '.', '');
    }

    /** paid / partial / due from the amounts — the single source of truth for status. */
    public function computedStatus(): string
    {
        if ((float) $this->paid_amount >= (float) $this->amount) {
            return 'paid';
        }
        return (float) $this->paid_amount > 0 ? 'partial' : 'due';
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Next sequential number for a prefix, e.g. INV-0007, RCPT-0012. */
    public static function nextNumber(string $column, string $prefix): string
    {
        $n = static::whereNotNull($column)->count() + 1;
        do {
            $candidate = $prefix.'-'.str_pad((string) $n++, 4, '0', STR_PAD_LEFT);
        } while (static::where($column, $candidate)->exists());

        return $candidate;
    }
}
