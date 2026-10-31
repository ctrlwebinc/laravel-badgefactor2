<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Courses;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class RelatedCourseGroupResource extends JsonResource
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
            'id'                    => $this->resource->id,
            'slug'                  => $this->resource->slug,
            'title'                 => $this->resource->title,
            'image'                 => $this->resource->getMedia('image')->first(),
            'course_group_category' => $this->resource->courseGroupCategory?->title,
        ];
    }
}
