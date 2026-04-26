<?php

namespace App\Http\Resources\Companies;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'nombre_empresa' => $this->nombre_empresa,
            'nombre_contacto' => $this->nombre_contacto,
            'telefono' => $this->telefono,
            'dirección' => $this->direccion,
            'foto' => $this->url_foto_perfil,
            'user' => $this->user,
        ];
    }
}
