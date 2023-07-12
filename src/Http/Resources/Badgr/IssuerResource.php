<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Badgr;

use Illuminate\Http\Resources\Json\JsonResource;

class IssuerResource extends JsonResource
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
            'id' => $this['entityId'],
            'name' => $this['name'],
            'email' => $this['email'],
        ];
    }
}
