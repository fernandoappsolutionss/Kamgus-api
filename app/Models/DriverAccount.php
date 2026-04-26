<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverAccount extends Model
{
    use HasFactory;
    protected $table = 'driver_accounts';
    protected $fillable = ["driver_id", "bank", "account_number"];
    public function driver(){
       
        return $this->belongsTo(Driver::class);
        
    }
}
