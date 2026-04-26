<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'is',
        'imagenable_id',
        'imagenable_type'
    ];

    public function imageable()
    {
        return $this->morphTo();
    }
    
}
