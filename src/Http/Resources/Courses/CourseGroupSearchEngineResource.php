<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Courses;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Ctrlweb\BadgeFactor2\Models\Badgr\Badge;

class CourseGroupSearchEngineResource extends JsonResource
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

        $courses = CourseResource::collection($this->resource->courses?->take(1) ?? null);

        if(!empty($courses)){

            $courses = $courses            
            ->map(function($course){
                $item = null;

                if($course->resource?->badgePage?->badgeclass_id){

                    $badge = Badge::find($course->resource?->badgePage?->badgeclass_id);

                    if($badge && $badge->issuer){
                        $item['badge_page']['issuer']['name'] = $badge->issuer->name;
                    }
                }

                return $item;

            })
            ->filter(function($course){
                return $course;
            });
        }
        

        return [
            'type'                     => 'course-group',
            'id'                       => $this->resource->id,
            'slug'                     => $this->resource->slug,
            'title'                    => $this->resource->title,
            'image'                    => $this->resource->getMedia('*')->first(),
            'courses'                  => $courses,
            'course_group_category'    => $this->resource->courseGroupCategory?->title ?? '',
            'createdAt'                => $this->resource->created_at,
            'updatedAt'                => $this->resource->updated_at,
            'is_featured'             =>  $this->resource->is_featured,
            'is_brandnew'             =>  $this->resource->is_brandnew,

        ];
    }
}
