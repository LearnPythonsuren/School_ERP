<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title', 'author', 'category', 'isbn',
        'total_copies', 'available_copies',
    ];

    protected function casts(): array
    {
        return [
            'total_copies'     => 'integer',
            'available_copies' => 'integer',
        ];
    }
}
