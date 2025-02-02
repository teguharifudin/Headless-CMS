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
        'is_active',
        'created_by'
    ];    

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
