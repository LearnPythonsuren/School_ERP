<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * A product-owner (Chenthur Info Tech) account for the central control
 * plane: issues licenses and provisions schools. Lives in the central DB.
 */
class CentralUser extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'central_users';

    protected $fillable = ['name', 'email', 'password', 'role'];
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
