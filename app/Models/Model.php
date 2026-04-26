<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Modelo_Eloquent;

class Model extends Modelo_Eloquent
{
    use HasFactory;
    protected $fillable = [
        "mark_id",
        "name",
        "status"
    ];
}
