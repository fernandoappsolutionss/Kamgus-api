<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['nombres', 'apellidos', 'telefono', 'direccion', 'url_foto_perfil', 'document_types_id', 'document_number'];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function document_type(){

        return $this->belongsTo(DocumentType::class, 'document_types_id', 'id');

    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function country(){

        return $this->belongsTo(Country::class);
        
    }

    public function accounts(){

        return $this->hasMany(DriverAccount::class);
        
    }

    public function services(){

        return $this->hasMany(Service::class);

    }

    public function vehicles(){

        return $this->hasMany(DriverVehicle::class);

    }

}

