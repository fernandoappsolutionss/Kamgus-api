<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['nombre_empresa','nombre_contacto','telefono','direccion','url_foto_perfil'];

    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}
