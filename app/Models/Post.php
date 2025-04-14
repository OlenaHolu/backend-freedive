<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_url',
        'description',
        'location',
        'hashtags',
    ];

    protected $casts = [
        'hashtags' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

