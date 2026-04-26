<?php

namespace App\Http\Resources\Customers;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tiempo' => $this->tiempo,
            'kilometraje' => $this->kilometraje,
            'fecha_reserva' => $this->fecha_reserva,
            'tipo_transporte' => $this->tipo_transporte,
            'tipo_servicio' => $this->tipo_servicio,
            'estado' => $this->estado,
            'precio_real' => $this->precio_real,
            'precio_sugerido' => $this->precio_sugerido,
            'tipo_pago' => $this->tipo_pago,
            'pago' => $this->pago,
            'descripcion' => $this->descripcion,
            'borrado' => $this->borrado,
            'driver' => $this->driver,
            'user'   => $this->user->load('roles', 'userable'),
            'ruta'     => $this->routes,
            // 'articles' => $this->customer_articles->concat($this->articles),
            'conductor_servicio' => $this->driver_service, 
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
