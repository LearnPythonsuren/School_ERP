<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title', 'body', 'channel', 'audience', 'recipients', 'status', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'recipients' => 'integer',
            'sent_at'    => 'datetime',
        ];
    }
}
