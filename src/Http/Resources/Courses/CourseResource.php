<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Courses;

use Ctrlweb\BadgeFactor2\Http\Resources\Badges\SimplifiedBadgePageResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\ECommerceHelper;
use Illuminate\Support\Facades\Auth;

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
            'course_group_id'         => $this->resource->course_group_id,
            'badge_page'              => SimplifiedBadgePageResource::make($this->resource->badgePage ?? null)->additional([
                'without' => [
                    'course',
                    'course_group',
                    'course_group_category',
                ],
            ]),
            'regular_price'           => $this->resource->regular_price,
            'needs_purchase'          => (null == Auth::user() ? null : ECommerceHelper::needsPurchase(Auth::user(), $this->resource)),
            'target_audiences'         => $this->resource->targetAudiences->pluck('title'),
            'technical_requirements'  => $this->resource->technicalRequirements->pluck('title'),
        ];
    }
}
