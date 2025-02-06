<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class PathwayPageResource extends JsonResource
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
            'type'                     => 'pathway-page',
            'id'                       => $this->resource->id,
            'slug'                     => $this->resource->slug,
            'title'                    => $this->resource->title,            
            'createdAt'                => $this->resource->created_at,
            'updatedAt'                => $this->resource->updated_at,
            'image'                     => $this->resource->getMedia('pathwayImages')->first(),
            'is_pathway'                => true,
            'is_autoformation'          => $this->resource->is_autoformation,
            'is_badgepage'              => $this->resource->is_badgepage
        ];
    }
}
