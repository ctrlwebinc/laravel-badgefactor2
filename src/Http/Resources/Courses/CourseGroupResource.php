<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Courses;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class CourseGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return [
            'id' => $this->resource->id,
            'slug' => $this->resource->slug,
            'excerpt' => substr($this->resource->excerpt, 0, 140),
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'image' => $this->resource->image,
            'content_specialists' => ResponsibleResource::collection($this->resource->contentSpecialists ?? null),
            'retroaction_responsibles' => ResponsibleResource::collection($this->resource->retroactionResponsibles ?? null),
            'courses' => CourseResource::collection($this->resource->courses ?? null),
            'course_group_category' => CourseGroupCategoryResource::make($this->resource->courseGroupCategory ?? null)->additional([
                'without' => [
                    'course_groups'
                ]
            ]),
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }
}
