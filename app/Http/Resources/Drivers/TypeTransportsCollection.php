<?php

namespace App\Http\Resources\Drivers;

use App\Constants\Constant;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TypeTransportsCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'msg' => Constant::LOAD_TYPES_TRANSPORTS,
        ];
    }
}
