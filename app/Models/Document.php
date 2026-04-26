<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = ['driver_id', "url_foto", "tipo"];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
