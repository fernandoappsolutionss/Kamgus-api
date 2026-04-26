<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "category_id",
        "state_id",
        "title",
        "description",
        "ended_at",
        "priority",
    ];
}
