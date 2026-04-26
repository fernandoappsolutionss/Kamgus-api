<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Model as Model_Vehicle;

class DriverVehicle extends Model
{
    use HasFactory;
    protected $fillable = ['id', 'driver_id', 'model_id', 'height', 'wide', 'long', 'plate', "m3", "color_id", "types_transport_id", "year", "burden", "status", "created_at"];
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function driver(){

        return $this->belongsTo(Driver::class);

    }

    public function color(){

        return $this->belongsTo(Color::class); 
    }

    public function type_transport(){

        return $this->belongsTo(TypeTransport::class, 'types_transport_id');

    }

    public function model(){

        return $this->belongsTo(Model_Vehicle::class);

    }
}
