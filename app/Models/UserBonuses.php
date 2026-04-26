<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBonuses extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "referred_id",
        "bond_value",
        "service_id",
        "used",
        "created_at",
        "updated_at",
    ];
    public function user(){
        
        return $this->belongsTo(User::class);

    }

    public function refered(){
        
        return $this->belongsTo(User::class, 'referred_id', 'id');

    }

}
