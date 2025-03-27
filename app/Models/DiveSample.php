<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiveSample extends Model
{
    protected $fillable = [
        'dive_id',
        'time',
        'depth',
        'temperature',
    ];

    public function dive()
    {
        return $this->belongsTo(Dive::class);
    }
}
