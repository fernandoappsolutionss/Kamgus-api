<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['name','url_imagen','m3','altura','ancho','largo','price','sub_category_id'];

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id');
    }

    /**
     * first realtionship with services
     */
    // public function services()
    // {
    //     return $this->belongsToMany(Service::class);
    // }

    public static function search($query=''){
        if (!$query) {
            return self::all();
        }
        return self::where('name', 'like', "%$query%")->get();
    }

    public function services(){

        return $this->morphToMany(Service::class, 'serviceable');

    }
}
