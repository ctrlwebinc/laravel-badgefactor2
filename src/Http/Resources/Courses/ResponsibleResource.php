<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Courses;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResponsibleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id'          => $this->resource->id,
            'name'        => $this->resource->name,
            'slug'        => $this->resource->slug,
            'description' => $this->resource->description,
            'image'       => $this->resource->image,
            'created_at'  => $this->resource->created_at,
        ];
    }
}
