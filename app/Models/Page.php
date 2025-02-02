<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'banner_media_id',
        'status',
        'published_at',
        'author_id'
    ];

    public function bannerMedia()
    {
        return $this->belongsTo(Media::class, 'banner_media_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
