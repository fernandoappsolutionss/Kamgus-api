<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class)->withPivot('status');
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class);
    }

    public function users(){

        return $this->belongsToMany(User::class);

    }
    
}
