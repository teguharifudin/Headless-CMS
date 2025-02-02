<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $fillable = [
        'name',
        'role',
        'bio',
        'profile_picture',
        'email',
        'order',
        'is_active'
    ];    

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];
}
