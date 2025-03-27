<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dive extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $fillable = [
        'user_id',
        'StartTime',
        'Duration',
        'Mode',
        'MaxDepth',
        'StartTemperature',
        'BottomTemperature',
        'EndTemperature',
        'PreviousMaxDepth',
        'Mode'
    ];

    public function samples()
    {
        return $this->hasMany(DiveSample::class);
    }
}
