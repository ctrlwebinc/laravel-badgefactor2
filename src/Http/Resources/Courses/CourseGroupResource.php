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
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return [
            'type'                     => 'course-group',
            'id'                       => $this->resource->id,
            'slug'                     => $this->resource->slug,
            'title'                    => $this->resource->title,
            'subtitle'                 => $this->resource->subtitle,
            'description'              => $this->resource->description,
            'excerpt'                  => mb_substr(strip_tags($this->resource->description), 0, 134, 'UTF-8').' [...]',
            'image'                    => $this->resource->getMedia('*')->first(),
            'balado'                   => $this->resource->balado, 
            'content_specialists'      => ResponsibleResource::collection($this->resource->contentSpecialists ?? null),
            'retroaction_responsibles' => ResponsibleResource::collection($this->resource->retroactionResponsibles ?? null),
            'courses'                  => CourseResource::collection($this->resource->courses ?? null),
            'course_group_category'    => CourseGroupCategoryResource::make($this->resource->courseGroupCategory ?? null)->additional([
                'without' => [
                    'course_groups',
                ],
            ]),
            'createdAt'                => $this->resource->created_at,
            'updatedAt'                => $this->resource->updated_at,
            'is_featured'             =>  $this->resource->is_featured,
            'is_brandnew'             =>  $this->resource->is_brandnew,

        ];
    }
}
