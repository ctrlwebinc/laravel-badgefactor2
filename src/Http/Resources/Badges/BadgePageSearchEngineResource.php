<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Badges;

use Carbon\Carbon;
use Cmixin\SeasonMixin;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\BasicCourseGroupResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseGroupCategoryResource;
use Ctrlweb\BadgeFactor2\Http\Resources\Courses\CourseResource;
use Ctrlweb\BadgeFactor2\Models\BadgeCategory;
use Ctrlweb\BadgeFactor2\Models\Badgr\Badge;
use Illuminate\Http\Resources\Json\JsonResource;

class BadgePageSearchEngineResource extends JsonResource
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

        $locale = app()->currentLocale();

        $badgeCategory = $this->resource->badge_category_id ? BadgeCategory::where('id', $this->resource->badge_category_id)->first() : null;
        if (null !== $badgeCategory) {
            $badgeCategory = $badgeCategory->title;
        }

        $badge = Badge::find($this->resource->badgeclass_id);

        

        return [
            'type'                  => 'badge-page',
            'id'                    => $this->resource->id,
            'badge_category'        => $badgeCategory,
            'title'                 => $this->resource->title,
            'slug'                  => $this->resource->slug,
            'badge_image'           => isset($badge->image) ? $badge->image : null,
            'issuer'                => ($badge != null && $badge->issuer?->name) ? ['name' => $badge->issuer?->name] : null,
            'image'                 => $this->resource->getMedia('*')->first(),            
            'createdAt'             => $this->resource->created_at,
            'updatedAt'             => $this->resource->updated_at,
            'is_featured'             =>  $this->resource->is_featured,
            'is_brandnew'             =>  $this->resource->is_brandnew
        ];
    }
}
