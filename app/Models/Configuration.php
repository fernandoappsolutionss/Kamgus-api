<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;
    const COMISION_KAMGUS = 1;
    const BALANCE_MINIMO_ID = 4;
    const IMPUESTO_PAIS = 5;
}
