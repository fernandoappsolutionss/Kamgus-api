<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['nombres','apellidos','telefono','direccion','url_foto_perfil', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

}
