<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $fillable = ['punto_inicial', 'latitud_inicial', 'longitud_inicial','punto_final','latitud_final','longitud_final'];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
