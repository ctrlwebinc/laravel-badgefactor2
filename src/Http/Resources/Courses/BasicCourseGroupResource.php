<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Courses;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class BasicCourseGroupResource extends JsonResource
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
            'id'          => $this->resource->id,
            'slug'        => $this->resource->slug,
            'excerpt'     => mb_substr(strip_tags($this->resource->description), 0, 134, 'UTF-8').' [...]',
            'description' => $this->resource->description,
            'title'       => $this->resource->title,
            'createdAt'   => $this->resource->created_at,
            'updatedAt'   => $this->resource->updated_at,
        ];
    }
}
