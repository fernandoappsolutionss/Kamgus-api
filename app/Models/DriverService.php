<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverService extends Model
{
    use HasFactory;
    protected $fillable = [
        "service_id",
        "endTime",
        "startTime",
        "driver_id",
        "status",
        "confirmed",
        "reservation_date",
        "observation",
        "suggested_price",
        "ispaid",
        "commission",
    ];
    
}
