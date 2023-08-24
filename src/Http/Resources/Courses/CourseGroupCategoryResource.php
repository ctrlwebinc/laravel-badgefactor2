<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Courses;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseGroupCategoryResource extends JsonResource
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
        $data = [
            'id'             => $this->resource->id,
            'title'          => $this->resource->title,
            'slug'           => $this->resource->slug,
            'is_featured'    => $this->resource->is_featured,
            'menu_title'     => $this->resource->menu_title,
            'excerpt'        => mb_substr(strip_tags($this->resource->description), 0, 134, 'UTF-8').' [...]',
            'label'          => $this->resource->title,
            'subtitle'       => $this->resource->subtitle,
            'description'    => $this->resource->description,
            'image'          => $this->resource->image,
            'featured_image' => $this->resource->image,
            'createdAt'      => $this->resource->created_at,
            'updatedAt'      => $this->resource->updated_at,
        ];

        $courseGroups = $this->resource->courseGroups;
        if (!isset($this->additional['without'])) {
            $data = array_merge($data, [
                'course_groups' => CourseGroupResource::collection($courseGroups ?? null),
            ]);
        }

        return $data;
    }
}
