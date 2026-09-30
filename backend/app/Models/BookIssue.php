<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookIssue extends Model
{
    protected $fillable = [
        'book_id', 'borrower', 'issued_on', 'due_on', 'returned_on', 'status',
    ];

    protected function casts(): array
    {
        return [
            'issued_on'   => 'date',
            'due_on'      => 'date',
            'returned_on' => 'date',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
