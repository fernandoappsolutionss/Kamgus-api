<?php

namespace App\Http\Resources\Customers;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
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
            'id'         => $this->id,
            'name'       => $this->name,
            'url_imagen' => $this->url_imagen,
            'm3'         => $this->m3,
            'altura'     => $this->altura,
            'ancho'      => $this->ancho,
            'largo'      => $this->largo,
            'price'      => $this->price,
            'sub_category' => $this->subcategory,
        ];
    }
}
