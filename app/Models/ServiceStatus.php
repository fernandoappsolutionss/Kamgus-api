<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        "id",
        "service_id",
        "status",
        "it_was_read",
        "description",
        "servicestatetable_id",
        "servicestatetable_type",
        "created_at",
        "updated_at",
    ];
}
