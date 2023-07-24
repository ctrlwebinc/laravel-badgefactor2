<?php

namespace Ctrlweb\BadgeFactor2\Http\Resources\Badges;

use Illuminate\Http\Resources\Json\JsonResource;

class BadgeGroupResource extends JsonResource
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
            'id'          => $this->id,
            'slug'        => $this->slug,
            'title'       => $this->title,
            'description' => $this->description,
            'image'       => $this->image,
            'createdAt'   => $this->created_at,
            'updatedAt'   => $this->updated_at,
        ];
    }
}
