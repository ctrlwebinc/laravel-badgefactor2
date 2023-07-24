<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Courses;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                      => $this->resource->id,
            'title'                   => $this->resource->title,
            'type'                    => $this->resource->type,
            'duration'                => $this->resource->duration,
            'url'                     => $this->resource->url,
            'autoevaluation_form_url' => $this->resource->autoevaluation_form_url,
            'created_at'              => $this->resource->created_at,
            'updated_at'              => $this->resource->updated_at,
            'product_id'              => $this->resource->product_id,
            'course_category_id'      => $this->resource->course_category_id,
            'course_group_id'         => $this->resource->course_group_id,
            'badge_page'              => $this->resource->badgePage,
            'regular_price'           => $this->resource->regular_price,
            'promo_price'             => $this->resource->promo_price
        ];
    }
}
