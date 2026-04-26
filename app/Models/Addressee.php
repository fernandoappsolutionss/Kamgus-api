<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addressee extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "doc_identifier",
        "service_id",
        "point",
    ];
    public function service(){

        return $this->belongsTo(Service::class);

    }
}
