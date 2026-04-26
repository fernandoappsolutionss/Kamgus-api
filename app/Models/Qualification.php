<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    use HasFactory;
    protected $fillable = [
        "qualification",
        "status",
        "service_id",
        "observation",
        "created_at",
        "updated_at",
    ];
}
