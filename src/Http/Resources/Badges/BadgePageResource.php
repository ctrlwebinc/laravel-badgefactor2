<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Badges;

use Ctrlweb\BadgeFactor2\Http\Resources\Courses\BasicCourseGroupResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseGroupCategoryResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseResource;
use Illuminate\Http\Resources\Json\JsonResource;

class BadgePageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'                    => $this->resource->id,
            'type'                  => $this->resource->type,
            'badgeclass_id'         => $this->resource->badgeclass_id,
            'title'                 => $this->resource->title,
            'slug'                  => $this->resource->slug,
            'content'               => $this->resource->content,
            'criteria'              => $this->resource->criteria,
            'approval_type'         => $this->resource->approval_type,
            'request_form_url'      => $this->resource->request_form_url,
            'course_category_id'    => $this->resource->course->course_category_id ?? null,
            'course_group_id'       => $this->resource->course->course_group_id ?? null,
            'last_updated_at'       => $this->resource->last_updated_at,
            'product_id'            => $this->resource->course->product_id ?? null,
            'course'                => CourseResource::make($this->resource->course ?? null),
            'course_group'          => BasicCourseGroupResource::make($this->resource->course->courseGroup ?? null),
            'course_group_category' => CourseGroupCategoryResource::make(
                $this->resource->course->courseGroup->courseGroupCategory ?? null
            )->additional(['without' => ['course_groups']]),
            'createdAt'             => $this->resource->created_at,
            'updatedAt'             => $this->resource->updated_at,
        ];
    }
}
