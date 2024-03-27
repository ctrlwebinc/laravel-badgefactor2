<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Badges;

use Carbon\Carbon;
use Cmixin\SeasonMixin;
use Ctrlweb\BadgeFactor2\Models\BadgeCategory;
use Ctrlweb\BadgeFactor2\Models\Badgr\Badge;
use Illuminate\Http\Resources\Json\JsonResource;

class SimplifiedBadgePageResource extends JsonResource
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
        if ($this->resource->last_updated_at) {
            Carbon::mixin(SeasonMixin::class);
            $season = Carbon::parse($this->resource->last_updated_at)->getSeason()->getName();
            switch ($season) {
                case 'fall':
                    // FIXME
                    //$season = __('Fall');
                    $season = 'Automne';
                    break;
                case 'winter':
                    // FIXME
                    //$season = __('Winter');
                    $season = 'Hiver';
                    break;
                case 'spring':
                    // FIXME
                    //$season = __('Spring');
                    $season = 'Printemps';
                    break;
                case 'summer':
                    // FIXME
                    //$season = __('Summer');
                    $season = 'Été';
                    break;
            }

            $season .= ' '.Carbon::parse($this->resource->last_updated_at)->year;
        } else {
            $season = null;
        }

        $locale = app()->currentLocale();

        $badgeCategory = $this->resource->badge_category_id ? BadgeCategory::where('id', $this->resource->badge_category_id)->first() : null;
        if (null !== $badgeCategory) {
            $badgeCategory = json_decode($badgeCategory->title)->fr;
        }

        $badge = Badge::find($this->resource->badgeclass_id);

        return [
            'id'                    => $this->resource->id,
            'badge_category'        => $badgeCategory,
            'badgeclass_id'         => $this->resource->badgeclass_id,
            'title'                 => $this->resource->title,
            'slug'                  => $this->resource->slug,
            'content'               => $this->resource->content,
            'criteria'              => $this->resource->criteria,
            'approval_type'         => $this->resource->approval_type,
            'request_form_url'      => $this->resource->request_form_url,
            'badge_image'           => $badge->image,
            'image'                 => $this->resource->getMedia('*')->first(),
            'video_url'             => $this->video_url,
            'course_group_id'       => $this->resource->course->course_group_id ?? null,
            'last_updated_at'       => $season,
            'product_id'            => $this->resource->course->product_id ?? null,
            'createdAt'             => $this->resource->created_at,
            'updatedAt'             => $this->resource->updated_at,
        ];
    }
}
