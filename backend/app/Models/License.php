<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class License extends Model
{
    protected $fillable = [
        'key', 'licensee', 'plan', 'status', 'tenant_id', 'issued_by', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public const VENDOR = 'Chenthur Info Tech';

    /** Generate a unique vendor-prefixed key, e.g. CHEN-4F9K-2QX7-8M1D. */
    public static function generateKey(): string
    {
        do {
            $key = 'CHEN-'.strtoupper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
        } while (self::where('key', $key)->exists());

        return $key;
    }

    public function isUsable(): bool
    {
        return $this->status === 'active'
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
