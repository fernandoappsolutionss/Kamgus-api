<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomArticle extends Model
{
    use HasFactory;

    public function services(){

        return $this->morphToMany(Service::class, 'serviceable');

    }

}
