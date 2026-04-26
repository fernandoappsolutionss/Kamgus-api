<?php

namespace App\Http\Resources\Drivers;

use Illuminate\Http\Resources\Json\JsonResource;

class TypeTransportsResource extends JsonResource
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
            'nombre' => $this->nombre,
            'm3' => $this->m3,
            'peso' => $this->peso,
            'precio_minuto' => $this->precio_minuto,
            'precio_ayudante' => $this->precio_ayudante,
            'descripcion' => $this->descripcion,
            "foto" => $this->foto,
            "url_foto" => $this->url_foto,
            "tiempo" => $this->tiempo,
            "estado" => $this->estado,
            "app_icon" =>  $this->app_icon,
            "app_icon_selected" => $this->app_icon_selected,
            "orden" => $this->orden,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at
        ];
    }
}
