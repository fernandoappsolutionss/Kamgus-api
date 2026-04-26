<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['tiempo','kilometraje','fecha_reserva','tipo_transporte','tipo_servicio','estado','precio_real','precio_sugerido','pago','descripcion','customer_id'];
    const ACTIVO_STATUS = 'ACTIVO';
    const AGENDADO_STATUS = 'AGENDADO';
    const INACTIVO_STATUS = 'INACTIVO';
    const PENDIENTE_STATUS = 'PENDIENTE';
    const CANCELADO_STATUS = 'CANCELADO';
    const ANULADO_STATUS = 'ANULADO';
    const TERMINADO_STATUS = 'TERMINADO';
    const RESERVA_STATUS = 'RESERVA';
    const PROGRAMAR_STATUS = 'PROGRAMAR';
    const REPETIR_STATUS = 'REPETIR';
    
    const SERVICE_ADMIN_OFFER = 'def'; //Constante definida para indicar que el cliente ha aceptado la oferta(precio sugerido) por defecto de un servicio nuevo.

    const CONVERT_S_TO_TT = [
        'PANEL' => "Panel",
        'PICK UP' => "Pick up",
        'CAMIÓN PEQUEÑO' => "Camión Pequeño",
        'CAMIÓN GRANDE' => "Camión Grande",
        'MOTO' => "Moto",
        'SEDAN' => "Sedan",
    ];
    const CONVERT_TT_TO_S = [
        "Panel" => 'PANEL',
        "Pick up" => 'PICK UP',
        "Camión Pequeño" => 'CAMIÓN PEQUEÑO',
        "Camión Grande" => 'CAMIÓN GRANDE',
        "Moto" => 'MOTO',
        "Sedan" => 'SEDAN',
    ];
    public function routes()
    {
        return $this->hasMany(Route::class);
    }

    /**
     * @first relation with article_service
    */
    // public function articles()
    // {
    //     return $this->belongsToMany(Article::class);
    // }

    public function customer_articles(){

        //'articleables', 'service_id', 'id', 'id', 'id' -> por si no agarra la relación
        return $this->morphedByMany(CustomArticle::class, 'serviceable');

    }

    public function articles(){

        return $this->morphedByMany(Article::class, 'serviceable');

    }
    
    public function driver(){

        return $this->belongsTo(Driver::class);

    }

    public function customer(){

        return $this->belongsTo(Customer::class);

    }

    public function transactions(){

        return $this->hasMany(Transaction::class);

    }
    
    public function scopeHistory($query, $initial_date, $final_date){

        // return $initial_date;
        return $query->whereBetween('created_at', [$initial_date, $final_date]);

    }

    public function scopeStatus($query, $status){

        if($status){
            return $query->where('estado', '=', $status);
        }

    }

    public function user(){

        return $this->belongsTo(User::class);

    }

    public function driver_service(){

        return $this->hasOne(DriverService::class, 'service_id');

    }

    public function image(){

        return $this->morphOne(Image::class, 'imageable');

    }
}
