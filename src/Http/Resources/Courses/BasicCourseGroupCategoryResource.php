<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Courses;

use Illuminate\Http\Resources\Json\JsonResource;

class BasicCourseGroupCategoryResource extends JsonResource
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
            'id'          => $this->resource->id,
            'title'       => $this->resource->title,
            'slug'        => $this->resource->slug,
            'is_featured' => $this->resource->is_featured,
            'menu_title'  => $this->resource->menu_title,
            'excerpt'     => substr($this->resource->excerpt, 0, 140),
            'label'       => $this->resource->title,
            'subtitle'    => $this->resource->subtitle,
            'description' => $this->resource->description,
            'image'       => $this->resource->image,
            'createdAt'   => $this->resource->created_at,
            'updatedAt'   => $this->resource->updated_at,
        ];

        return $data;
    }
}
