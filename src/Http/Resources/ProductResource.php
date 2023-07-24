<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return [
            "id"            => $this->resource->id,
            "name"          => $this->resource->name,
            "description"   => $this->resource->description,
            "regular_price" => $this->resource->regular_price,
            "promo_price"   => $this->resource->promo_price,
        ];
    }
}
