<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /** Page size from ?per_page=, clamped so one request can't dump a whole table. */
    protected function perPage(Request $request, int $default = 25): int
    {
        return max(1, min(200, $request->integer('per_page', $default)));
    }

    /** Lifetime for newly issued API tokens (SANCTUM_EXPIRATION minutes, else 30 days). */
    protected function tokenExpiry(): \DateTimeInterface
    {
        return now()->addMinutes((int) (config('sanctum.expiration') ?: 60 * 24 * 30));
    }
}
