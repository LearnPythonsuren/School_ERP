<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    protected $table = 'staff_members';

    protected $fillable = [
        'name',
        'role',
        'department',
        'status',   // present | absent | leave
        'email',
        'phone',
    ];
}
